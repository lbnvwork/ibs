<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702082532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add id column to patient_vitals_latest table and set it as primary key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient_vitals_latest DROP CONSTRAINT patient_vitals_latest_pkey');
        $this->addSql('ALTER TABLE patient_vitals_latest ADD COLUMN id SERIAL PRIMARY KEY');
        $this->addSql('ALTER TABLE patient_vitals_latest ADD CONSTRAINT uniq_patient_id UNIQUE (patient_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient_vitals_latest DROP CONSTRAINT uniq_patient_id');
        $this->addSql('ALTER TABLE patient_vitals_latest DROP COLUMN id');
        $this->addSql('ALTER TABLE patient_vitals_latest ADD PRIMARY KEY (patient_id)');
    }
}
