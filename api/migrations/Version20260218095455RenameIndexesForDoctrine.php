<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218095455RenameIndexesForDoctrine extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE treatmentcodegenerator_id_seq CASCADE');
        $this->addSql('ALTER TABLE drugs DROP CONSTRAINT fk_drugs_group');
        $this->addSql('ALTER TABLE drugs ADD CONSTRAINT FK_DA2C39DAFE54D947 FOREIGN KEY (group_id) REFERENCES drug_groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_drugs_group RENAME TO IDX_DA2C39DAFE54D947');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE treatmentcodegenerator_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE drugs DROP CONSTRAINT FK_DA2C39DAFE54D947');
        $this->addSql('ALTER TABLE drugs ADD CONSTRAINT fk_drugs_group FOREIGN KEY (group_id) REFERENCES drug_groups (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_da2c39dafe54d947 RENAME TO idx_drugs_group');
    }
}
