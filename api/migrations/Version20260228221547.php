<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228221547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_treatment_patient_id RENAME TO IDX_4A48CE0D6B899279');
        $this->addSql('ALTER INDEX idx_treatment_drug_id RENAME TO IDX_4A48CE0DAABCA765');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT fk_users_medpers');
        $this->addSql('ALTER TABLE users ALTER roles SET NOT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E939EDBB1A FOREIGN KEY (medical_personnel_id) REFERENCES medical_personnel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_users_medpers RENAME TO IDX_1483A5E939EDBB1A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E939EDBB1A');
        $this->addSql('ALTER TABLE users ALTER roles DROP NOT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT fk_users_medpers FOREIGN KEY (medical_personnel_id) REFERENCES medical_personnel (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_1483a5e939edbb1a RENAME TO idx_users_medpers');
        $this->addSql('ALTER INDEX idx_4a48ce0daabca765 RENAME TO idx_treatment_drug_id');
        $this->addSql('ALTER INDEX idx_4a48ce0d6b899279 RENAME TO idx_treatment_patient_id');
    }
}
