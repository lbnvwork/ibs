<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625110100_2_15_audit extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Задача 2.15: аудит — добавление полей created_by и updated_by в patient_genetic_results';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('patient_genetic_results');

        if (!$table->hasColumn('created_by')) {
            $this->addSql('ALTER TABLE patient_genetic_results ADD COLUMN created_by VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('updated_by')) {
            $this->addSql('ALTER TABLE patient_genetic_results ADD COLUMN updated_by VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('patient_genetic_results');

        if ($table->hasColumn('created_by')) {
            $this->addSql('ALTER TABLE patient_genetic_results DROP COLUMN created_by');
        }

        if ($table->hasColumn('updated_by')) {
            $this->addSql('ALTER TABLE patient_genetic_results DROP COLUMN updated_by');
        }
    }
}
