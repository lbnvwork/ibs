<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627135155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавление полей created_by и updated_by в patient_vitals (аудит)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient_vitals ADD COLUMN created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient_vitals ADD COLUMN updated_by VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient_vitals DROP COLUMN created_by');
        $this->addSql('ALTER TABLE patient_vitals DROP COLUMN updated_by');
    }
}
