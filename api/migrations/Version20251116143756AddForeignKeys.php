<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251116143756AddForeignKeys extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key constraints (corrected for existing columns)';
    }

    public function up(Schema $schema): void
    {
        // Внешние ключи для НОВЫХ столбцов

        // MedicalPersonnel -> Hospital (новый столбец)
        $this->addSql('ALTER TABLE medical_personnel ADD CONSTRAINT fk_medical_personnel_hospital_id FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // MedicalPersonnelPhone -> PhoneType (новый столбец)
        $this->addSql('ALTER TABLE med_personnel_phones ADD CONSTRAINT fk_med_personnel_phones_phone_type_id FOREIGN KEY (phone_type_id) REFERENCES phone_types (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // MedicalPersonnelPhone -> MedicalPersonnel (новый столбец)
        $this->addSql('ALTER TABLE med_personnel_phones ADD CONSTRAINT fk_med_personnel_phones_person_id FOREIGN KEY (person_id) REFERENCES medical_personnel (id) ON DELETE CASCADE ON UPDATE CASCADE;');

        // Patient -> Hospital (новый столбец)
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT fk_patients_hospital_id FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // PatientPhone -> PhoneType (новый столбец)
        $this->addSql('ALTER TABLE patient_phones ADD CONSTRAINT fk_patient_phones_phone_type_id FOREIGN KEY (phone_type_id) REFERENCES phone_types (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // PatientPhone -> Patient (новый столбец)
        $this->addSql('ALTER TABLE patient_phones ADD CONSTRAINT fk_patient_phones_person_id FOREIGN KEY (person_id) REFERENCES patients (id) ON DELETE CASCADE ON UPDATE CASCADE;');

        // Treatment -> Patient (новый столбец)
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT fk_treatments_patient_id FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // Treatment -> Drug (новый столбец)
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT fk_treatments_drug_id FOREIGN KEY (drug_id) REFERENCES drugs (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // TreatmentNote -> Treatment (новый столбец)
        $this->addSql('ALTER TABLE treatment_notes ADD CONSTRAINT fk_treatment_notes_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // Appointment -> Treatment (новый столбец)
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT fk_appointments_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // Appointment -> SmsOut (новый столбец)
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT fk_appointments_sms_id FOREIGN KEY (sms_id) REFERENCES sms_out (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // Appointment -> Drug (новый столбец)
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT fk_appointments_drug_id FOREIGN KEY (drug_id) REFERENCES drugs (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // TestPlan -> Treatment (новый столбец)
        $this->addSql('ALTER TABLE test_plans ADD CONSTRAINT fk_test_plans_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // HospitalTestPlan -> Hospital (новый столбец)
        $this->addSql('ALTER TABLE hospital_test_plans ADD CONSTRAINT fk_hospital_test_plans_hospital_id FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // TestHistory -> Treatment (новый столбец)
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT fk_test_history_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // TestHistory -> Drug (новый столбец)
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT fk_test_history_drug_id FOREIGN KEY (drug_id) REFERENCES drugs (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // MedPersonnelUser -> MedicalPersonnel (новый столбец)
        $this->addSql('ALTER TABLE med_personnel_users ADD CONSTRAINT fk_med_personnel_users_medical_personnel_id FOREIGN KEY (medical_personnel_id) REFERENCES medical_personnel (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // TestHistoryByLaborant -> TestHistory (новый столбец)
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT fk_test_histories_by_assistant_test_history_id FOREIGN KEY (test_history_id) REFERENCES test_history (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // TestHistoryByLaborant -> MedicalPersonnel (новый столбец)
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD CONSTRAINT fk_test_histories_by_assistant_assistant_id FOREIGN KEY (assistant_id) REFERENCES medical_personnel (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // UserForHospital -> User (новый столбец)
        $this->addSql('ALTER TABLE users_for_hospitals ADD CONSTRAINT fk_users_for_hospitals_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE;');

        // UserForHospital -> Hospital (новый столбец)
        $this->addSql('ALTER TABLE users_for_hospitals ADD CONSTRAINT fk_users_for_hospitals_hospital_id FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE CASCADE ON UPDATE CASCADE;');

        // Supervisor -> User (новый столбец)
        $this->addSql('ALTER TABLE supervisors ADD CONSTRAINT fk_supervisors_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE;');

        // Supervisor -> HospitalTestPlan (новый столбец)
        $this->addSql('ALTER TABLE supervisors ADD CONSTRAINT fk_supervisors_plan_id FOREIGN KEY (plan_id) REFERENCES hospital_test_plans (id) ON DELETE CASCADE ON UPDATE CASCADE;');

        // PatientRequest -> Treatment (новый столбец)
        $this->addSql('ALTER TABLE patient_requests ADD CONSTRAINT fk_patient_requests_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // Внешние ключи для СУЩЕСТВУЮЩИХ столбцов (из исходной миграции)

        // SmsOutStatus -> SmsOut (существующий столбец)
        $this->addSql('ALTER TABLE sms_out_statuses ADD CONSTRAINT fk_sms_out_statuses_sms_id FOREIGN KEY (sms_id) REFERENCES sms_out (id) ON DELETE CASCADE ON UPDATE CASCADE;');

        // TestHistory -> SmsOut (существующий столбец)
        $this->addSql('ALTER TABLE test_history ADD CONSTRAINT fk_test_history_sms_id FOREIGN KEY (sms_id) REFERENCES sms_out (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // SmsIn -> Treatment (существующий столбец)
        $this->addSql('ALTER TABLE sms_in ADD CONSTRAINT fk_sms_in_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON DELETE SET NULL ON UPDATE CASCADE;');

        // SmsOut -> Treatment (существующий столбец)
        $this->addSql('ALTER TABLE sms_out ADD CONSTRAINT fk_sms_out_treatment_id FOREIGN KEY (treatment_id) REFERENCES treatments (id) ON DELETE SET NULL ON UPDATE CASCADE;');
    }

    public function down(Schema $schema): void
    {
        // Удаляем все внешние ключи
        $this->addSql('ALTER TABLE medical_personnel DROP CONSTRAINT fk_medical_personnel_hospital_id;');
        $this->addSql('ALTER TABLE med_personnel_phones DROP CONSTRAINT fk_med_personnel_phones_phone_type_id;');
        $this->addSql('ALTER TABLE med_personnel_phones DROP CONSTRAINT fk_med_personnel_phones_person_id;');
        $this->addSql('ALTER TABLE patients DROP CONSTRAINT fk_patients_hospital_id;');
        $this->addSql('ALTER TABLE patient_phones DROP CONSTRAINT fk_patient_phones_phone_type_id;');
        $this->addSql('ALTER TABLE patient_phones DROP CONSTRAINT fk_patient_phones_person_id;');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT fk_treatments_patient_id;');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT fk_treatments_drug_id;');
        $this->addSql('ALTER TABLE treatment_notes DROP CONSTRAINT fk_treatment_notes_treatment_id;');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT fk_appointments_treatment_id;');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT fk_appointments_sms_id;');
        $this->addSql('ALTER TABLE appointments DROP CONSTRAINT fk_appointments_drug_id;');
        $this->addSql('ALTER TABLE test_plans DROP CONSTRAINT fk_test_plans_treatment_id;');
        $this->addSql('ALTER TABLE hospital_test_plans DROP CONSTRAINT fk_hospital_test_plans_hospital_id;');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT fk_test_history_treatment_id;');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT fk_test_history_drug_id;');
        $this->addSql('ALTER TABLE test_history DROP CONSTRAINT fk_test_history_sms_id;');
        $this->addSql('ALTER TABLE med_personnel_users DROP CONSTRAINT fk_med_personnel_users_medical_personnel_id;');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT fk_test_histories_by_assistant_test_history_id;');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP CONSTRAINT fk_test_histories_by_assistant_assistant_id;');
        $this->addSql('ALTER TABLE sms_out_statuses DROP CONSTRAINT fk_sms_out_statuses_sms_id;');
        $this->addSql('ALTER TABLE users_for_hospitals DROP CONSTRAINT fk_users_for_hospitals_user_id;');
        $this->addSql('ALTER TABLE users_for_hospitals DROP CONSTRAINT fk_users_for_hospitals_hospital_id;');
        $this->addSql('ALTER TABLE supervisors DROP CONSTRAINT fk_supervisors_user_id;');
        $this->addSql('ALTER TABLE supervisors DROP CONSTRAINT fk_supervisors_plan_id;');
        $this->addSql('ALTER TABLE patient_requests DROP CONSTRAINT fk_patient_requests_treatment_id;');
        $this->addSql('ALTER TABLE sms_in DROP CONSTRAINT fk_sms_in_treatment_id;');
        $this->addSql('ALTER TABLE sms_out DROP CONSTRAINT fk_sms_out_treatment_id;');
    }
}