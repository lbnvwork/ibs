<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630112706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add weight column to patient_vitals and patient_vitals_latest tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient_vitals ADD COLUMN weight DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE patient_vitals_latest ADD COLUMN weight DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient_vitals DROP COLUMN weight');
        $this->addSql('ALTER TABLE patient_vitals_latest DROP COLUMN weight');
    }
}
