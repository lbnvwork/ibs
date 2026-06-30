<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Задача 2.21: создание таблиц patient_vitals и patient_vitals_latest';
    }

    public function up(Schema $schema): void
    {
        // 1. Таблица patient_vitals
        $this->addSql('CREATE TABLE patient_vitals (
            id SERIAL PRIMARY KEY,
            patient_id INT NOT NULL,
            treatment_id INT DEFAULT NULL,
            record_dt TIMESTAMP(0) NOT NULL DEFAULT NOW(),
            hb DOUBLE PRECISION DEFAULT NULL,
            heart_rate INT DEFAULT NULL,
            systolic_pressure INT DEFAULT NULL,
            diastolic_pressure INT DEFAULT NULL,
            saturation INT DEFAULT NULL,
            comment TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) NOT NULL DEFAULT NOW()
        )');

        // Внешние ключи
        $this->addSql('ALTER TABLE patient_vitals ADD CONSTRAINT fk_vitals_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patient_vitals ADD CONSTRAINT fk_vitals_treatment FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE SET NULL');

        // Индексы
        $this->addSql('CREATE INDEX idx_vitals_patient_record ON patient_vitals (patient_id, record_dt DESC)');
        $this->addSql('CREATE INDEX idx_vitals_treatment ON patient_vitals (treatment_id)');

        // CHECK ограничение "хотя бы один показатель"
        $this->addSql('ALTER TABLE patient_vitals ADD CONSTRAINT chk_at_least_one CHECK (
            hb IS NOT NULL OR
            heart_rate IS NOT NULL OR
            systolic_pressure IS NOT NULL OR
            diastolic_pressure IS NOT NULL OR
            saturation IS NOT NULL
        )');

        // 2. Таблица patient_vitals_latest
        $this->addSql('CREATE TABLE patient_vitals_latest (
            patient_id INT PRIMARY KEY,
            hb DOUBLE PRECISION DEFAULT NULL,
            heart_rate INT DEFAULT NULL,
            systolic_pressure INT DEFAULT NULL,
            diastolic_pressure INT DEFAULT NULL,
            saturation INT DEFAULT NULL,
            last_updated TIMESTAMP(0) NOT NULL DEFAULT NOW()
        )');

        // Внешний ключ на patients
        $this->addSql('ALTER TABLE patient_vitals_latest ADD CONSTRAINT fk_vitals_latest_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Откат: удаляем таблицы
        $this->addSql('DROP TABLE IF EXISTS patient_vitals_latest');
        $this->addSql('DROP TABLE IF EXISTS patient_vitals');
    }
}