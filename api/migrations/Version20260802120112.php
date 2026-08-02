<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260802120112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointments (id SERIAL NOT NULL, treatment_id INT DEFAULT NULL, sms_id INT DEFAULT NULL, drug_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, appointment_dt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, doze DOUBLE PRECISION NOT NULL, doctor_name TEXT NOT NULL, comment TEXT DEFAULT NULL, doze2 INT DEFAULT -1 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6A41727A471C0366 ON appointments (treatment_id)');
        $this->addSql('CREATE INDEX IDX_6A41727ABD5C7E60 ON appointments (sms_id)');
        $this->addSql('CREATE INDEX IDX_6A41727AAABCA765 ON appointments (drug_id)');
        $this->addSql('CREATE TABLE drug_groups (id SERIAL NOT NULL, name VARCHAR(50) NOT NULL, full_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE drugs (id SERIAL NOT NULL, group_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, nominative TEXT DEFAULT NULL, genitive TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_drug_group_id ON drugs (group_id)');
        $this->addSql('CREATE TABLE genetic_marker_values (id SERIAL NOT NULL, marker_id INT NOT NULL, value VARCHAR(50) NOT NULL, label VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B1C6BC1E474460EB ON genetic_marker_values (marker_id)');
        $this->addSql('CREATE TABLE genetic_markers (id SERIAL NOT NULL, gene_symbol VARCHAR(30) NOT NULL, full_name VARCHAR(150) NOT NULL, rs_id VARCHAR(50) DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE holidays (id SERIAL NOT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, h_month INT NOT NULL, h_day INT NOT NULL, comment TEXT DEFAULT NULL, h_year INT DEFAULT 2015 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE hospital_test_plans (id SERIAL NOT NULL, hospital_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, test_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status INT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4E1E37CE63DBB69 ON hospital_test_plans (hospital_id)');
        $this->addSql('CREATE TABLE hospitals (id SERIAL NOT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name TEXT DEFAULT NULL, region TEXT DEFAULT NULL, sms_phone TEXT DEFAULT NULL, address TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE marker_drug_relations (id SERIAL NOT NULL, marker_id INT NOT NULL, drug_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E3C034B5474460EB ON marker_drug_relations (marker_id)');
        $this->addSql('CREATE INDEX IDX_E3C034B5AABCA765 ON marker_drug_relations (drug_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_marker_drug ON marker_drug_relations (marker_id, drug_id)');
        $this->addSql('CREATE TABLE med_personnel_phones (id SERIAL NOT NULL, phone_type_id INT DEFAULT NULL, person_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, number TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_801D98F1550E00A1 ON med_personnel_phones (phone_type_id)');
        $this->addSql('CREATE INDEX IDX_801D98F1217BBB47 ON med_personnel_phones (person_id)');
        $this->addSql('CREATE TABLE medical_personnel (id SERIAL NOT NULL, hospital_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name TEXT DEFAULT NULL, post TEXT DEFAULT NULL, address TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_53CFAD6463DBB69 ON medical_personnel (hospital_id)');
        $this->addSql('CREATE TABLE metadata (id SERIAL NOT NULL, version INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE mkb10 (id INT NOT NULL, rec_code VARCHAR(50) DEFAULT NULL, mkb_code VARCHAR(50) DEFAULT NULL, mkb_name TEXT DEFAULT NULL, id_parent INT DEFAULT NULL, addl_code INT DEFAULT NULL, actual INT DEFAULT NULL, date VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE patient_genetic_results (id SERIAL NOT NULL, patient_id INT NOT NULL, marker_id INT NOT NULL, marker_value_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2BEC85AF6B899279 ON patient_genetic_results (patient_id)');
        $this->addSql('CREATE INDEX IDX_2BEC85AF474460EB ON patient_genetic_results (marker_id)');
        $this->addSql('CREATE INDEX IDX_2BEC85AF198FDE2D ON patient_genetic_results (marker_value_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_patient_marker ON patient_genetic_results (patient_id, marker_id)');
        $this->addSql('CREATE TABLE patient_phones (id SERIAL NOT NULL, phone_type_id INT DEFAULT NULL, person_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, number TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E866C70F550E00A1 ON patient_phones (phone_type_id)');
        $this->addSql('CREATE INDEX IDX_E866C70F217BBB47 ON patient_phones (person_id)');
        $this->addSql('CREATE TABLE patient_requests (id SERIAL NOT NULL, treatment_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, reason TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4C008EF4471C0366 ON patient_requests (treatment_id)');
        $this->addSql('CREATE TABLE patient_vitals (id SERIAL NOT NULL, patient_id INT NOT NULL, treatment_id INT DEFAULT NULL, record_dt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, hb DOUBLE PRECISION DEFAULT NULL, heart_rate INT DEFAULT NULL, systolic_pressure INT DEFAULT NULL, diastolic_pressure INT DEFAULT NULL, saturation INT DEFAULT NULL, comment TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5ED921486B899279 ON patient_vitals (patient_id)');
        $this->addSql('CREATE INDEX IDX_5ED92148471C0366 ON patient_vitals (treatment_id)');
        $this->addSql('CREATE INDEX idx_vitals_patient_record ON patient_vitals (patient_id, record_dt)');
        $this->addSql('CREATE TABLE patient_vitals_latest (id SERIAL NOT NULL, patient_id INT NOT NULL, hb DOUBLE PRECISION DEFAULT NULL, heart_rate INT DEFAULT NULL, systolic_pressure INT DEFAULT NULL, diastolic_pressure INT DEFAULT NULL, saturation INT DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, last_updated TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7E8DFED86B899279 ON patient_vitals_latest (patient_id)');
        $this->addSql('CREATE TABLE patients (id SERIAL NOT NULL, hospital_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, firstname TEXT NOT NULL, second_name TEXT DEFAULT NULL, lastname TEXT NOT NULL, birthday TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sex INT DEFAULT 0 NOT NULL, sms_phone TEXT NOT NULL, address TEXT DEFAULT NULL, passport TEXT DEFAULT NULL, health_insurance TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, snils TEXT DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2CCC2E2C63DBB69 ON patients (hospital_id)');
        $this->addSql('CREATE TABLE phone_types (id SERIAL NOT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name TEXT DEFAULT NULL, mask TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE sms_in (id SERIAL NOT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, treatment_id INT DEFAULT NULL, server_id INT DEFAULT NULL, sms_source VARCHAR(11) DEFAULT NULL, num VARCHAR(11) DEFAULT NULL, text TEXT DEFAULT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status INT DEFAULT NULL, dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE sms_out (id SERIAL NOT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, treatment_id INT DEFAULT NULL, sms_source VARCHAR(11) DEFAULT NULL, sms_target VARCHAR(11) NOT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, text TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE sms_out_packets (id SERIAL NOT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, server_packet_id INT NOT NULL, balance TEXT DEFAULT NULL, server_code TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE sms_out_statuses (id SERIAL NOT NULL, sms_id INT DEFAULT NULL, server_code TEXT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, packet_id INT DEFAULT NULL, server_id INT DEFAULT NULL, phone_zone INT DEFAULT NULL, parts INT DEFAULT NULL, credits INT DEFAULT NULL, status INT DEFAULT NULL, error TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B5F9687EBD5C7E60 ON sms_out_statuses (sms_id)');
        $this->addSql('CREATE TABLE sms_templates (id SERIAL NOT NULL, sms_type INT DEFAULT NULL, sms_source TEXT DEFAULT NULL, sms_template TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE supervisors (id SERIAL NOT NULL, user_id INT DEFAULT NULL, plan_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A82524B7A76ED395 ON supervisors (user_id)');
        $this->addSql('CREATE INDEX IDX_A82524B7E899029B ON supervisors (plan_id)');
        $this->addSql('CREATE TABLE test_histories_by_assistant (id SERIAL NOT NULL, test_history_id INT DEFAULT NULL, assistant_id INT DEFAULT NULL, user_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1539B7596536326B ON test_histories_by_assistant (test_history_id)');
        $this->addSql('CREATE INDEX IDX_1539B759E05387EF ON test_histories_by_assistant (assistant_id)');
        $this->addSql('CREATE INDEX IDX_1539B759A76ED395 ON test_histories_by_assistant (user_id)');
        $this->addSql('CREATE TABLE test_history (id SERIAL NOT NULL, treatment_id INT DEFAULT NULL, drug_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, sms_id INT DEFAULT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, mno DOUBLE PRECISION NOT NULL, doze DOUBLE PRECISION NOT NULL, doze2 INT DEFAULT -1 NOT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FBFBA483471C0366 ON test_history (treatment_id)');
        $this->addSql('CREATE INDEX IDX_FBFBA483AABCA765 ON test_history (drug_id)');
        $this->addSql('CREATE TABLE test_plans (id SERIAL NOT NULL, treatment_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, test_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status INT DEFAULT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3EB93EDA471C0366 ON test_plans (treatment_id)');
        $this->addSql('CREATE TABLE treatment_code_generator (id SERIAL NOT NULL, code INT NOT NULL, generate INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE treatment_notes (id SERIAL NOT NULL, treatment_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6B6845B2471C0366 ON treatment_notes (treatment_id)');
        $this->addSql('CREATE TABLE treatments (id SERIAL NOT NULL, patient_id INT DEFAULT NULL, drug_id INT DEFAULT NULL, mkb10_id INT DEFAULT NULL, mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code INT DEFAULT NULL, diagnosis TEXT NOT NULL, comorbidities TEXT DEFAULT NULL, mno_from DOUBLE PRECISION NOT NULL, mno_to DOUBLE PRECISION NOT NULL, beg_dt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, plan_end_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, real_end_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, stopping_reason TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, hemorrhages INT DEFAULT 0 NOT NULL, flags INT DEFAULT 0 NOT NULL, diagnosis_code VARCHAR(255) DEFAULT NULL, pin INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4A48CE0D77153098 ON treatments (code)');
        $this->addSql('CREATE INDEX IDX_4A48CE0D172BA9F8 ON treatments (mkb10_id)');
        $this->addSql('CREATE INDEX idx_treatment_patient_id ON treatments (patient_id)');
        $this->addSql('CREATE INDEX idx_treatment_patient_beg_dt ON treatments (patient_id, beg_dt)');
        $this->addSql('CREATE INDEX idx_treatment_drug_id ON treatments (drug_id)');
        $this->addSql('CREATE TABLE users (id SERIAL NOT NULL, medical_personnel_id INT DEFAULT NULL, login TEXT DEFAULT NULL, password TEXT DEFAULT NULL, user_name TEXT DEFAULT NULL, roles JSON DEFAULT \'[]\' NOT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_users_medpers ON users (medical_personnel_id)');
        $this->addSql('CREATE TABLE users_for_hospitals (id SERIAL NOT NULL, user_id INT DEFAULT NULL, hospital_id INT DEFAULT NULL, permissions INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1DEC5F93A76ED395 ON users_for_hospitals (user_id)');
        $this->addSql('CREATE INDEX IDX_1DEC5F9363DBB69 ON users_for_hospitals (hospital_id)');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727ABD5C7E60 FOREIGN KEY (sms_id) REFERENCES sms_out (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727AAABCA765 FOREIGN KEY (drug_id) REFERENCES drugs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE drugs ADD CONSTRAINT FK_DA2C39DAFE54D947 FOREIGN KEY (group_id) REFERENCES drug_groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE genetic_marker_values ADD CONSTRAINT FK_B1C6BC1E474460EB FOREIGN KEY (marker_id) REFERENCES genetic_markers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE hospital_test_plans ADD CONSTRAINT FK_4E1E37CE63DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE marker_drug_relations ADD CONSTRAINT FK_E3C034B5474460EB FOREIGN KEY (marker_id) REFERENCES genetic_markers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE marker_drug_relations ADD CONSTRAINT FK_E3C034B5AABCA765 FOREIGN KEY (drug_id) REFERENCES drugs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE med_personnel_phones ADD CONSTRAINT FK_801D98F1550E00A1 FOREIGN KEY (phone_type_id) REFERENCES phone_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE med_personnel_phones ADD CONSTRAINT FK_801D98F1217BBB47 FOREIGN KEY (person_id) REFERENCES medical_personnel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE medical_personnel ADD CONSTRAINT FK_53CFAD6463DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_genetic_results ADD CONSTRAINT FK_2BEC85AF6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_genetic_results ADD CONSTRAINT FK_2BEC85AF474460EB FOREIGN KEY (marker_id) REFERENCES genetic_markers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_genetic_results ADD CONSTRAINT FK_2BEC85AF198FDE2D FOREIGN KEY (marker_value_id) REFERENCES genetic_marker_values (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_phones ADD CONSTRAINT FK_E866C70F550E00A1 FOREIGN KEY (phone_type_id) REFERENCES phone_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_phones ADD CONSTRAINT FK_E866C70F217BBB47 FOREIGN KEY (person_id) REFERENCES patients (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_requests ADD CONSTRAINT FK_4C008EF4471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_vitals ADD CONSTRAINT FK_5ED921486B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_vitals ADD CONSTRAINT FK_5ED92148471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_vitals_latest ADD CONSTRAINT FK_7E8DFED86B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT FK_2CCC2E2C63DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sms_out_statuses ADD CONSTRAINT FK_B5F9687EBD5C7E60 FOREIGN KEY (sms_id) REFERENCES sms_out (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervisors ADD CONSTRAINT FK_A82524B7A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervisors ADD CONSTRAINT FK_A82524B7E899029B FOREIGN KEY (plan_id) REFERENCES hospital_test_plans (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT FK_1539B7596536326B FOREIGN KEY (test_history_id) REFERENCES test_history (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT FK_1539B759E05387EF FOREIGN KEY (assistant_id) REFERENCES medical_personnel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT FK_1539B759A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT FK_FBFBA483471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT FK_FBFBA483AABCA765 FOREIGN KEY (drug_id) REFERENCES drugs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_plans ADD CONSTRAINT FK_3EB93EDA471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatment_notes ADD CONSTRAINT FK_6B6845B2471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT FK_4A48CE0D6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT FK_4A48CE0DAABCA765 FOREIGN KEY (drug_id) REFERENCES drugs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT FK_4A48CE0D172BA9F8 FOREIGN KEY (mkb10_id) REFERENCES mkb10 (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E939EDBB1A FOREIGN KEY (medical_personnel_id) REFERENCES medical_personnel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_for_hospitals ADD CONSTRAINT FK_1DEC5F93A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_for_hospitals ADD CONSTRAINT FK_1DEC5F9363DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT FK_6A41727A471C0366');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT FK_6A41727ABD5C7E60');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT FK_6A41727AAABCA765');
        $this->addSql('ALTER TABLE drugs DROP CONSTRAINT FK_DA2C39DAFE54D947');
        $this->addSql('ALTER TABLE genetic_marker_values DROP CONSTRAINT FK_B1C6BC1E474460EB');
        $this->addSql('ALTER TABLE hospital_test_plans DROP CONSTRAINT FK_4E1E37CE63DBB69');
        $this->addSql('ALTER TABLE marker_drug_relations DROP CONSTRAINT FK_E3C034B5474460EB');
        $this->addSql('ALTER TABLE marker_drug_relations DROP CONSTRAINT FK_E3C034B5AABCA765');
        $this->addSql('ALTER TABLE med_personnel_phones DROP CONSTRAINT FK_801D98F1550E00A1');
        $this->addSql('ALTER TABLE med_personnel_phones DROP CONSTRAINT FK_801D98F1217BBB47');
        $this->addSql('ALTER TABLE medical_personnel DROP CONSTRAINT FK_53CFAD6463DBB69');
        $this->addSql('ALTER TABLE patient_genetic_results DROP CONSTRAINT FK_2BEC85AF6B899279');
        $this->addSql('ALTER TABLE patient_genetic_results DROP CONSTRAINT FK_2BEC85AF474460EB');
        $this->addSql('ALTER TABLE patient_genetic_results DROP CONSTRAINT FK_2BEC85AF198FDE2D');
        $this->addSql('ALTER TABLE patient_phones DROP CONSTRAINT FK_E866C70F550E00A1');
        $this->addSql('ALTER TABLE patient_phones DROP CONSTRAINT FK_E866C70F217BBB47');
        $this->addSql('ALTER TABLE patient_requests DROP CONSTRAINT FK_4C008EF4471C0366');
        $this->addSql('ALTER TABLE patient_vitals DROP CONSTRAINT FK_5ED921486B899279');
        $this->addSql('ALTER TABLE patient_vitals DROP CONSTRAINT FK_5ED92148471C0366');
        $this->addSql('ALTER TABLE patient_vitals_latest DROP CONSTRAINT FK_7E8DFED86B899279');
        $this->addSql('ALTER TABLE patients DROP CONSTRAINT FK_2CCC2E2C63DBB69');
        $this->addSql('ALTER TABLE sms_out_statuses DROP CONSTRAINT FK_B5F9687EBD5C7E60');
        $this->addSql('ALTER TABLE supervisors DROP CONSTRAINT FK_A82524B7A76ED395');
        $this->addSql('ALTER TABLE supervisors DROP CONSTRAINT FK_A82524B7E899029B');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT FK_1539B7596536326B');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT FK_1539B759E05387EF');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT FK_1539B759A76ED395');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT FK_FBFBA483471C0366');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT FK_FBFBA483AABCA765');
        $this->addSql('ALTER TABLE test_plans DROP CONSTRAINT FK_3EB93EDA471C0366');
        $this->addSql('ALTER TABLE treatment_notes DROP CONSTRAINT FK_6B6845B2471C0366');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT FK_4A48CE0D6B899279');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT FK_4A48CE0DAABCA765');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT FK_4A48CE0D172BA9F8');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E939EDBB1A');
        $this->addSql('ALTER TABLE users_for_hospitals DROP CONSTRAINT FK_1DEC5F93A76ED395');
        $this->addSql('ALTER TABLE users_for_hospitals DROP CONSTRAINT FK_1DEC5F9363DBB69');
        $this->addSql('DROP TABLE appointments');
        $this->addSql('DROP TABLE drug_groups');
        $this->addSql('DROP TABLE drugs');
        $this->addSql('DROP TABLE genetic_marker_values');
        $this->addSql('DROP TABLE genetic_markers');
        $this->addSql('DROP TABLE holidays');
        $this->addSql('DROP TABLE hospital_test_plans');
        $this->addSql('DROP TABLE hospitals');
        $this->addSql('DROP TABLE marker_drug_relations');
        $this->addSql('DROP TABLE med_personnel_phones');
        $this->addSql('DROP TABLE medical_personnel');
        $this->addSql('DROP TABLE metadata');
        $this->addSql('DROP TABLE mkb10');
        $this->addSql('DROP TABLE patient_genetic_results');
        $this->addSql('DROP TABLE patient_phones');
        $this->addSql('DROP TABLE patient_requests');
        $this->addSql('DROP TABLE patient_vitals');
        $this->addSql('DROP TABLE patient_vitals_latest');
        $this->addSql('DROP TABLE patients');
        $this->addSql('DROP TABLE phone_types');
        $this->addSql('DROP TABLE sms_in');
        $this->addSql('DROP TABLE sms_out');
        $this->addSql('DROP TABLE sms_out_packets');
        $this->addSql('DROP TABLE sms_out_statuses');
        $this->addSql('DROP TABLE sms_templates');
        $this->addSql('DROP TABLE supervisors');
        $this->addSql('DROP TABLE test_histories_by_assistant');
        $this->addSql('DROP TABLE test_history');
        $this->addSql('DROP TABLE test_plans');
        $this->addSql('DROP TABLE treatment_code_generator');
        $this->addSql('DROP TABLE treatment_notes');
        $this->addSql('DROP TABLE treatments');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_for_hospitals');
    }
}
