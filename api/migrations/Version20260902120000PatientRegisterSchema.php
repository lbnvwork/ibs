<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * 3.31 Подготовка схемы к импорту регистра пациентов.
 * Новое: patient_anamnesis, patients.consent/max_messenger,
 * treatments.antiplatelet_drug_id/antiplatelet_doze,
 * patient_vitals.creatinine, patient_vitals_latest.creatinine.
 */
final class Version20260902120000PatientRegisterSchema extends AbstractMigration
{
    public function getDescription(): string
    {
        return '3.31 Patient register schema: anamnesis + consent/maxMessenger + antiplatelet + creatinine';
    }

    public function up(Schema $schema): void
    {
        // 1. Справочники: типы сахарного диабета + стадии ХБП
        $this->addSql('CREATE TABLE diabetes_types (
            id SERIAL NOT NULL,
            name VARCHAR(50) NOT NULL,
            full_name VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql("INSERT INTO diabetes_types (id, name, full_name) VALUES (1, 'СД1', 'Сахарный диабет 1 типа'), (2, 'СД2', 'Сахарный диабет 2 типа')");
        $this->addSql("SELECT setval('diabetes_types_id_seq', 2, true)");
        $this->addSql('CREATE TABLE ckd_stages (
            id SERIAL NOT NULL,
            name VARCHAR(50) NOT NULL,
            full_name VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql("INSERT INTO ckd_stages (id, name, full_name) VALUES (1, 'Стадия 1', 'Хроническая болезнь почек, стадия 1'), (2, 'Стадия 2', 'Хроническая болезнь почек, стадия 2'), (3, 'Стадия 3', 'Хроническая болезнь почек, стадия 3'), (4, 'Стадия 4', 'Хроническая болезнь почек, стадия 4'), (5, 'Стадия 5', 'Хроническая болезнь почек, стадия 5')");
        $this->addSql("SELECT setval('ckd_stages_id_seq', 5, true)");

        // 2. Анамнез (OneToOne с patients, владеющая сторона — анамнез)
        $this->addSql('CREATE TABLE patient_anamnesis (
            id SERIAL NOT NULL,
            patient_id INT NOT NULL,
            mk BOOLEAN DEFAULT NULL,
            ak BOOLEAN DEFAULT NULL,
            tk BOOLEAN DEFAULT NULL,
            lk BOOLEAN DEFAULT NULL,
            diabetes_type_id INT DEFAULT NULL,
            stroke_hemorrhagic BOOLEAN DEFAULT NULL,
            stroke_ischemic BOOLEAN DEFAULT NULL,
            ckd_stage_id INT DEFAULT NULL,
            acs_id INT DEFAULT NULL,
            cha2ds2_vasc INT DEFAULT NULL,
            has_bled INT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_562E52566B899279 ON patient_anamnesis (patient_id)');
        $this->addSql('CREATE INDEX IDX_562E52564C726391 ON patient_anamnesis (acs_id)');
        $this->addSql('CREATE INDEX IDX_562E52569E366159 ON patient_anamnesis (diabetes_type_id)');
        $this->addSql('CREATE INDEX IDX_562E525665B14DBC ON patient_anamnesis (ckd_stage_id)');
        $this->addSql('ALTER TABLE patient_anamnesis ADD CONSTRAINT fk_patient_anamnesis_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_anamnesis ADD CONSTRAINT fk_patient_anamnesis_acs FOREIGN KEY (acs_id) REFERENCES mkb10 (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_anamnesis ADD CONSTRAINT fk_patient_anamnesis_diabetes FOREIGN KEY (diabetes_type_id) REFERENCES diabetes_types (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_anamnesis ADD CONSTRAINT fk_patient_anamnesis_ckd FOREIGN KEY (ckd_stage_id) REFERENCES ckd_stages (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // 2. patients: согласие + флаг MAX
        $this->addSql('ALTER TABLE patients ADD consent BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE patients ADD max_messenger BOOLEAN DEFAULT NULL');

        // 3. treatments: антиагрегант
        $this->addSql('ALTER TABLE treatments ADD antiplatelet_drug_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE treatments ADD antiplatelet_doze TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_treatment_antiplatelet_drug_id ON treatments (antiplatelet_drug_id)');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT fk_treatment_antiplatelet_drug FOREIGN KEY (antiplatelet_drug_id) REFERENCES drugs (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // 4. patient_vitals + patient_vitals_latest: креатинин
        $this->addSql('ALTER TABLE patient_vitals ADD creatinine DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE patient_vitals_latest ADD creatinine DOUBLE PRECISION DEFAULT NULL');

        // 6. Русские комментарии новых таблиц/колонок (расшифрованные аббревиатуры)
        $this->addSql("COMMENT ON TABLE diabetes_types IS 'Типы сахарного диабета (справочник)'");
        $this->addSql("COMMENT ON COLUMN diabetes_types.id IS 'Идентификатор'");
        $this->addSql("COMMENT ON COLUMN diabetes_types.name IS 'Краткое название (СД1/СД2)'");
        $this->addSql("COMMENT ON COLUMN diabetes_types.full_name IS 'Полное название (сахарный диабет 1/2 типа)'");
        $this->addSql("COMMENT ON TABLE ckd_stages IS 'Стадии хронической болезни почек (справочник)'");
        $this->addSql("COMMENT ON COLUMN ckd_stages.id IS 'Идентификатор'");
        $this->addSql("COMMENT ON COLUMN ckd_stages.name IS 'Краткое название (Стадия N)'");
        $this->addSql("COMMENT ON COLUMN ckd_stages.full_name IS 'Полное название (хроническая болезнь почек, стадия N)'");
        $this->addSql("COMMENT ON TABLE patient_anamnesis IS 'Анамнез пациента'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.id IS 'Идентификатор'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.patient_id IS 'Пациент'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.mk IS 'Митральный клапан'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.ak IS 'Аортальный клапан'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.tk IS 'Трикуспидальный клапан'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.lk IS 'Лёгочный клапан'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.diabetes_type_id IS 'Сахарный диабет (справочник)'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.stroke_hemorrhagic IS 'Острое нарушение мозгового кровообращения (геморрагический)'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.stroke_ischemic IS 'Острое нарушение мозгового кровообращения (ишемический)'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.ckd_stage_id IS 'Хроническая болезнь почек, стадия (справочник)'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.acs_id IS 'Острый коронарный синдром (код МКБ-10)'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.cha2ds2_vasc IS 'Шкала CHA₂DS₂-VASc (риск инсульта, 0..9)'");
        $this->addSql("COMMENT ON COLUMN patient_anamnesis.has_bled IS 'Шкала HAS-BLED (риск кровотечения, 0..9)'");
        $this->addSql("COMMENT ON COLUMN patients.consent IS 'Согласие пациента (информированное добровольное согласие)'");
        $this->addSql("COMMENT ON COLUMN patients.max_messenger IS 'Наличие мессенджера MAX'");
        $this->addSql("COMMENT ON COLUMN treatments.antiplatelet_drug_id IS 'Антиагрегант'");
        $this->addSql("COMMENT ON COLUMN treatments.antiplatelet_doze IS 'Доза антиагреганта'");
        $this->addSql("COMMENT ON COLUMN patient_vitals.creatinine IS 'Креатинин (мкмоль/л)'");
        $this->addSql("COMMENT ON COLUMN patient_vitals_latest.creatinine IS 'Креатинин (мкмоль/л)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient_vitals_latest DROP creatinine');
        $this->addSql('ALTER TABLE patient_vitals DROP creatinine');

        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT fk_treatment_antiplatelet_drug');
        $this->addSql('DROP INDEX idx_treatment_antiplatelet_drug_id');
        $this->addSql('ALTER TABLE treatments DROP antiplatelet_doze');
        $this->addSql('ALTER TABLE treatments DROP antiplatelet_drug_id');

        $this->addSql('ALTER TABLE patients DROP max_messenger');
        $this->addSql('ALTER TABLE patients DROP consent');

        $this->addSql('DROP TABLE patient_anamnesis');
        $this->addSql('DROP TABLE ckd_stages');
        $this->addSql('DROP TABLE diabetes_types');
    }
}
