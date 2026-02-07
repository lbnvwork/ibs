<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251116143705_AddMissingColumns extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns for foreign keys';
    }

    public function up(Schema $schema): void
    {
        // MedicalPersonnel
        $this->addSql('ALTER TABLE medical_personnel ADD COLUMN hospital_id INTEGER DEFAULT NULL;');

        // MedicalPersonnelPhone
        $this->addSql('ALTER TABLE med_personnel_phones ADD COLUMN phone_type_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE med_personnel_phones ADD COLUMN person_id INTEGER DEFAULT NULL;');

        // Patient
        $this->addSql('ALTER TABLE patients ADD COLUMN hospital_id INTEGER DEFAULT NULL;');

        // PatientPhone
        $this->addSql('ALTER TABLE patient_phones ADD COLUMN phone_type_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE patient_phones ADD COLUMN person_id INTEGER DEFAULT NULL;');

        // Treatment
        $this->addSql('ALTER TABLE treatments ADD COLUMN patient_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE treatments ADD COLUMN drug_id INTEGER DEFAULT NULL;');

        // TreatmentNote
        $this->addSql('ALTER TABLE treatment_notes ADD COLUMN treatment_id INTEGER DEFAULT NULL;');

        // Appointment
        $this->addSql('ALTER TABLE appointments ADD COLUMN treatment_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE appointments ADD COLUMN sms_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE appointments ADD COLUMN drug_id INTEGER DEFAULT NULL;');

        // TestPlan
        $this->addSql('ALTER TABLE test_plans ADD COLUMN treatment_id INTEGER DEFAULT NULL;');

        // HospitalTestPlan
        $this->addSql('ALTER TABLE hospital_test_plans ADD COLUMN hospital_id INTEGER DEFAULT NULL;');

        // TestHistory - добавляем ТОЛЬКО treatment_id и drug_id (sms_id уже есть в исходной)
        $this->addSql('ALTER TABLE test_history ADD COLUMN treatment_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE test_history ADD COLUMN drug_id INTEGER DEFAULT NULL;');

        // MedPersonnelUser
        $this->addSql('ALTER TABLE med_personnel_users ADD COLUMN medical_personnel_id INTEGER DEFAULT NULL;');

        // TestHistoryByAssistant
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD COLUMN test_history_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE test_histories_by_assistant ADD COLUMN assistant_id INTEGER DEFAULT NULL;');

        // UserForHospital
        $this->addSql('ALTER TABLE users_for_hospitals ADD COLUMN user_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE users_for_hospitals ADD COLUMN hospital_id INTEGER DEFAULT NULL;');

        // Supervisor
        $this->addSql('ALTER TABLE supervisors ADD COLUMN user_id INTEGER DEFAULT NULL;');
        $this->addSql('ALTER TABLE supervisors ADD COLUMN plan_id INTEGER DEFAULT NULL;');

        // PatientRequest
        $this->addSql('ALTER TABLE patient_requests ADD COLUMN treatment_id INTEGER DEFAULT NULL;');

        // sms_out_statuses - НИЧЕГО не добавляем, все столбцы уже есть в исходной
        // test_history.sms_id - уже есть в исходной
        // sms_in.treatment_id - уже есть в исходной
        // sms_out.treatment_id - уже есть в исходной
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE medical_personnel DROP COLUMN hospital_id;');
        $this->addSql('ALTER TABLE med_personnel_phones DROP COLUMN phone_type_id;');
        $this->addSql('ALTER TABLE med_personnel_phones DROP COLUMN person_id;');
        $this->addSql('ALTER TABLE patients DROP COLUMN hospital_id;');
        $this->addSql('ALTER TABLE patient_phones DROP COLUMN phone_type_id;');
        $this->addSql('ALTER TABLE patient_phones DROP COLUMN person_id;');
        $this->addSql('ALTER TABLE treatments DROP COLUMN patient_id;');
        $this->addSql('ALTER TABLE treatments DROP COLUMN drug_id;');
        $this->addSql('ALTER TABLE treatment_notes DROP COLUMN treatment_id;');
        $this->addSql('ALTER TABLE appointments DROP COLUMN treatment_id;');
        $this->addSql('ALTER TABLE appointments DROP COLUMN sms_id;');
        $this->addSql('ALTER TABLE appointments DROP COLUMN drug_id;');
        $this->addSql('ALTER TABLE test_plans DROP COLUMN treatment_id;');
        $this->addSql('ALTER TABLE hospital_test_plans DROP COLUMN hospital_id;');
        $this->addSql('ALTER TABLE test_history DROP COLUMN treatment_id;');
        $this->addSql('ALTER TABLE test_history DROP COLUMN drug_id;');
        $this->addSql('ALTER TABLE med_personnel_users DROP COLUMN medical_personnel_id;');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP COLUMN test_history_id;');
        $this->addSql('ALTER TABLE test_histories_by_assistant DROP COLUMN assistant_id;');
        $this->addSql('ALTER TABLE users_for_hospitals DROP COLUMN user_id;');
        $this->addSql('ALTER TABLE users_for_hospitals DROP COLUMN hospital_id;');
        $this->addSql('ALTER TABLE supervisors DROP COLUMN user_id;');
        $this->addSql('ALTER TABLE supervisors DROP COLUMN plan_id;');
        $this->addSql('ALTER TABLE patient_requests DROP COLUMN treatment_id;');
    }
}