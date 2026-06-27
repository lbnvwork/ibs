<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627121147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE patient_vitals ALTER record_dt DROP DEFAULT');
        $this->addSql('ALTER TABLE patient_vitals ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE patient_vitals ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_vitals_treatment RENAME TO IDX_5ED92148471C0366');
        $this->addSql('ALTER TABLE patient_vitals_latest ALTER last_updated DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE patient_vitals_latest ALTER last_updated SET DEFAULT \'now()\'');
        $this->addSql('ALTER TABLE patient_vitals ALTER record_dt SET DEFAULT \'now()\'');
        $this->addSql('ALTER TABLE patient_vitals ALTER created_at SET DEFAULT \'now()\'');
        $this->addSql('ALTER TABLE patient_vitals ALTER updated_at SET DEFAULT \'now()\'');
        $this->addSql('ALTER INDEX idx_5ed92148471c0366 RENAME TO idx_vitals_treatment');
    }
}
