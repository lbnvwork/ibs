<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109201059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE appointments (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                appointment_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                doze DOUBLE PRECISION DEFAULT NULL, 
                doctor_name TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE drugs (
                id SERIAL NOT NULL,
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                nominative TEXT DEFAULT NULL,
                genitive TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE holidays (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                h_month INT DEFAULT NULL, 
                h_day INT DEFAULT NULL, 
                h_year INT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE hospital_test_plans (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                test_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                status INT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE hospitals (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                name TEXT DEFAULT NULL, 
                region TEXT DEFAULT NULL, 
                sms_phone TEXT DEFAULT NULL, 
                address TEXT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE med_personnel_phones (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                number TEXT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE med_personnel_users (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                login TEXT DEFAULT NULL, 
                password TEXT DEFAULT NULL, 
                roles INT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE medical_personnel (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                name TEXT DEFAULT NULL, 
                post TEXT DEFAULT NULL, 
                address TEXT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE metadata (
                id SERIAL NOT NULL, 
                version INT NOT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE patient_phones (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                number TEXT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE patient_requests (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                reason TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE patients (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                firstname TEXT DEFAULT NULL, 
                second_name TEXT DEFAULT NULL, 
                lastname TEXT DEFAULT NULL, 
                birthday TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                sex INT DEFAULT NULL, 
                sms_phone TEXT DEFAULT NULL, 
                address TEXT DEFAULT NULL, 
                passport TEXT DEFAULT NULL, 
                health_insurance TEXT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE phone_types (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                name TEXT DEFAULT NULL, 
                mask TEXT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE sms_in (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                treatment_id INT DEFAULT NULL, 
                server_id INT DEFAULT NULL, 
                sms_source INT DEFAULT NULL, 
                num INT DEFAULT NULL, 
                text INT DEFAULT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                status INT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE sms_out (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                treatment_id INT DEFAULT NULL, 
                sms_source TEXT DEFAULT NULL, 
                sms_target INT NOT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE sms_out_packets (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                server_packet_id INT DEFAULT NULL, 
                balance TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE sms_out_statuses (
                id SERIAL NOT NULL, 
                sms_id INT DEFAULT NULL, 
                server_code TEXT DEFAULT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                packet_id INT DEFAULT NULL, 
                server_id INT DEFAULT NULL, 
                phone_zone INT DEFAULT NULL, 
                parts INT DEFAULT NULL, 
                credits INT DEFAULT NULL, 
                status INT DEFAULT NULL, 
                error TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE sms_templates (
                id SERIAL NOT NULL, 
                sms_type INT DEFAULT NULL, 
                sms_source TEXT DEFAULT NULL, 
                sms_template TEXT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE supervisors (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE test_histories_by_assistant (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE test_history (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                sms_id INT DEFAULT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                mno DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE test_plans (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                test_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                status INT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE treatmentCodeGenerator (
                id SERIAL NOT NULL, 
                code INT NOT NULL, 
                generate INT NOT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE treatment_notes (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                creation_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                note TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');
        $this->addSql('
            CREATE TABLE treatments (
                id SERIAL NOT NULL, 
                mod_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                code INT DEFAULT NULL, 
                diagnosis TEXT DEFAULT NULL, 
                comorbidities TEXT DEFAULT NULL, 
                mno_from DOUBLE PRECISION DEFAULT NULL, 
                mno_to DOUBLE PRECISION DEFAULT NULL, 
                beg_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                plan_end_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                real_end_dt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                stopping_reason TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE users (
                id SERIAL NOT NULL, 
                login TEXT DEFAULT NULL, 
                password TEXT DEFAULT NULL, 
                user_name TEXT DEFAULT NULL, 
                roles INT DEFAULT NULL, 
                comment TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');

        $this->addSql('
            CREATE TABLE users_for_hospitals (
                id SERIAL NOT NULL, 
                permissions INT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE appointments');
        $this->addSql('DROP TABLE drugs');
        $this->addSql('DROP TABLE holidays');
        $this->addSql('DROP TABLE hospital_test_plans');
        $this->addSql('DROP TABLE hospitals');
        $this->addSql('DROP TABLE med_personnel_phones');
        $this->addSql('DROP TABLE med_personnel_users');
        $this->addSql('DROP TABLE medical_personnel');
        $this->addSql('DROP TABLE metadata');
        $this->addSql('DROP TABLE patient_phones');
        $this->addSql('DROP TABLE patient_requests');
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
        $this->addSql('DROP TABLE treatmentCodeGenerator');
        $this->addSql('DROP TABLE treatment_notes');
        $this->addSql('DROP TABLE treatments');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_for_hospitals');
    }
}
