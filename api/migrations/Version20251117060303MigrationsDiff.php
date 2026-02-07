<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117060303MigrationsDiff extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT fk_appointments_drug_id');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT fk_appointments_sms_id');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT fk_appointments_treatment_id');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727A471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727ABD5C7E60 FOREIGN KEY (sms_id) REFERENCES sms_out (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_6A41727AAABCA765 FOREIGN KEY (drug_id) REFERENCES drugs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE hospital_test_plans DROP CONSTRAINT fk_hospital_test_plans_hospital_id');
        $this->addSql('ALTER TABLE hospital_test_plans ADD CONSTRAINT FK_4E1E37CE63DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE med_personnel_phones DROP CONSTRAINT fk_med_personnel_phones_person_id');
        $this->addSql('ALTER TABLE med_personnel_phones DROP CONSTRAINT fk_med_personnel_phones_phone_type_id');
        $this->addSql('ALTER TABLE med_personnel_phones ADD CONSTRAINT FK_801D98F1550E00A1 FOREIGN KEY (phone_type_id) REFERENCES phone_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE med_personnel_phones ADD CONSTRAINT FK_801D98F1217BBB47 FOREIGN KEY (person_id) REFERENCES medical_personnel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE med_personnel_users DROP CONSTRAINT fk_med_personnel_users_medical_personnel_id');
        $this->addSql('ALTER TABLE med_personnel_users ADD CONSTRAINT FK_275978F439EDBB1A FOREIGN KEY (medical_personnel_id) REFERENCES medical_personnel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE medical_personnel DROP CONSTRAINT fk_medical_personnel_hospital_id');
        $this->addSql('ALTER TABLE medical_personnel ADD CONSTRAINT FK_53CFAD6463DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_phones DROP CONSTRAINT fk_patient_phones_person_id');
        $this->addSql('ALTER TABLE patient_phones DROP CONSTRAINT fk_patient_phones_phone_type_id');
        $this->addSql('ALTER TABLE patient_phones ADD CONSTRAINT FK_E866C70F550E00A1 FOREIGN KEY (phone_type_id) REFERENCES phone_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_phones ADD CONSTRAINT FK_E866C70F217BBB47 FOREIGN KEY (person_id) REFERENCES patients (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_requests DROP CONSTRAINT fk_patient_requests_treatment_id');
        $this->addSql('ALTER TABLE patient_requests ADD CONSTRAINT FK_4C008EF4471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patients DROP CONSTRAINT fk_patients_hospital_id');
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT FK_2CCC2E2C63DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sms_in DROP CONSTRAINT fk_sms_in_treatment_id');
        $this->addSql('ALTER TABLE sms_out DROP CONSTRAINT fk_sms_out_treatment_id');
        $this->addSql('ALTER TABLE sms_out_statuses DROP CONSTRAINT fk_sms_out_statuses_sms_id');
        $this->addSql('ALTER TABLE sms_out_statuses ADD CONSTRAINT FK_B5F9687EBD5C7E60 FOREIGN KEY (sms_id) REFERENCES sms_out (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervisors DROP CONSTRAINT fk_supervisors_plan_id');
        $this->addSql('ALTER TABLE supervisors DROP CONSTRAINT fk_supervisors_user_id');
        $this->addSql('ALTER TABLE supervisors ADD CONSTRAINT FK_A82524B7A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervisors ADD CONSTRAINT FK_A82524B7E899029B FOREIGN KEY (plan_id) REFERENCES hospital_test_plans (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT fk_test_histories_by_assistant_assistant_id');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT fk_test_histories_by_assistant_test_history_id');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT FK_1539B7596536326B FOREIGN KEY (test_history_id) REFERENCES test_history (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT FK_1539B759E05387EF FOREIGN KEY (assistant_id) REFERENCES medical_personnel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT fk_test_history_drug_id');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT fk_test_history_sms_id');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT fk_test_history_treatment_id');
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT FK_FBFBA483471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT FK_FBFBA483AABCA765 FOREIGN KEY (drug_id) REFERENCES drugs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_plans DROP CONSTRAINT fk_test_plans_treatment_id');
        $this->addSql('ALTER TABLE test_plans ADD CONSTRAINT FK_3EB93EDA471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatment_notes DROP CONSTRAINT fk_treatment_notes_treatment_id');
        $this->addSql('ALTER TABLE treatment_notes ADD CONSTRAINT FK_6B6845B2471C0366 FOREIGN KEY (treatment_id) REFERENCES treatments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT fk_treatments_drug_id');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT fk_treatments_patient_id');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT FK_4A48CE0D6B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT FK_4A48CE0DAABCA765 FOREIGN KEY (drug_id) REFERENCES drugs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_for_hospitals DROP CONSTRAINT fk_users_for_hospitals_hospital_id');
        $this->addSql('ALTER TABLE users_for_hospitals DROP CONSTRAINT fk_users_for_hospitals_user_id');
        $this->addSql('ALTER TABLE users_for_hospitals ADD CONSTRAINT FK_1DEC5F93A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_for_hospitals ADD CONSTRAINT FK_1DEC5F9363DBB69 FOREIGN KEY (hospital_id) REFERENCES hospitals (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE med_personnel_phones DROP CONSTRAINT FK_801D98F1550E00A1');
        $this->addSql('ALTER TABLE med_personnel_phones DROP CONSTRAINT FK_801D98F1217BBB47');
        $this->addSql('ALTER TABLE med_personnel_phones ADD CONSTRAINT fk_med_personnel_phones_person_id FOREIGN KEY (person_id) REFERENCES medical_personnel (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE med_personnel_phones ADD CONSTRAINT fk_med_personnel_phones_phone_type_id FOREIGN KEY (phone_type_id) REFERENCES phone_types (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_phones DROP CONSTRAINT FK_E866C70F550E00A1');
        $this->addSql('ALTER TABLE patient_phones DROP CONSTRAINT FK_E866C70F217BBB47');
        $this->addSql('ALTER TABLE patient_phones ADD CONSTRAINT fk_patient_phones_person_id FOREIGN KEY (person_id) REFERENCES patients (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_phones ADD CONSTRAINT fk_patient_phones_phone_type_id FOREIGN KEY (phone_type_id) REFERENCES phone_types (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT FK_1539B7596536326B');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT FK_1539B759E05387EF');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT fk_test_histories_by_assistant_assistant_id FOREIGN KEY (assistant_id) REFERENCES medical_personnel (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT fk_test_histories_by_assistant_test_history_id FOREIGN KEY (test_history_id) REFERENCES test_history (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE hospital_test_plans DROP CONSTRAINT FK_4E1E37CE63DBB69');
        $this->addSql('ALTER TABLE hospital_test_plans ADD CONSTRAINT fk_hospital_test_plans_hospital_id FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervisors DROP CONSTRAINT FK_A82524B7A76ED395');
        $this->addSql('ALTER TABLE supervisors DROP CONSTRAINT FK_A82524B7E899029B');
        $this->addSql('ALTER TABLE supervisors ADD CONSTRAINT fk_supervisors_plan_id FOREIGN KEY (plan_id) REFERENCES hospital_test_plans (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE supervisors ADD CONSTRAINT fk_supervisors_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatment_notes DROP CONSTRAINT FK_6B6845B2471C0366');
        $this->addSql('ALTER TABLE treatment_notes ADD CONSTRAINT fk_treatment_notes_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT FK_FBFBA483471C0366');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT FK_FBFBA483AABCA765');
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT fk_test_history_drug_id FOREIGN KEY (drug_id) REFERENCES drugs (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT fk_test_history_sms_id FOREIGN KEY (sms_id) REFERENCES sms_out (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT fk_test_history_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE medical_personnel DROP CONSTRAINT FK_53CFAD6463DBB69');
        $this->addSql('ALTER TABLE medical_personnel ADD CONSTRAINT fk_medical_personnel_hospital_id FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patients DROP CONSTRAINT FK_2CCC2E2C63DBB69');
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT fk_patients_hospital_id FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE test_plans DROP CONSTRAINT FK_3EB93EDA471C0366');
        $this->addSql('ALTER TABLE test_plans ADD CONSTRAINT fk_test_plans_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT FK_4A48CE0D6B899279');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT FK_4A48CE0DAABCA765');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT fk_treatments_drug_id FOREIGN KEY (drug_id) REFERENCES drugs (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT fk_treatments_patient_id FOREIGN KEY (patient_id) REFERENCES patients (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sms_in ADD CONSTRAINT fk_sms_in_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT FK_6A41727A471C0366');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT FK_6A41727ABD5C7E60');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT FK_6A41727AAABCA765');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT fk_appointments_drug_id FOREIGN KEY (drug_id) REFERENCES drugs (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT fk_appointments_sms_id FOREIGN KEY (sms_id) REFERENCES sms_out (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT fk_appointments_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE patient_requests DROP CONSTRAINT FK_4C008EF4471C0366');
        $this->addSql('ALTER TABLE patient_requests ADD CONSTRAINT fk_patient_requests_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sms_out ADD CONSTRAINT fk_sms_out_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_for_hospitals DROP CONSTRAINT FK_1DEC5F93A76ED395');
        $this->addSql('ALTER TABLE users_for_hospitals DROP CONSTRAINT FK_1DEC5F9363DBB69');
        $this->addSql('ALTER TABLE users_for_hospitals ADD CONSTRAINT fk_users_for_hospitals_hospital_id FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users_for_hospitals ADD CONSTRAINT fk_users_for_hospitals_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE med_personnel_users DROP CONSTRAINT FK_275978F439EDBB1A');
        $this->addSql('ALTER TABLE med_personnel_users ADD CONSTRAINT fk_med_personnel_users_medical_personnel_id FOREIGN KEY (medical_personnel_id) REFERENCES medical_personnel (id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sms_out_statuses DROP CONSTRAINT FK_B5F9687EBD5C7E60');
        $this->addSql('ALTER TABLE sms_out_statuses ADD CONSTRAINT fk_sms_out_statuses_sms_id FOREIGN KEY (sms_id) REFERENCES sms_out (id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
