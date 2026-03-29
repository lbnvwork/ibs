<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328105750RenameConstraint extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter treatments table: change foreign key constraint on mkb10_id to set ON DELETE SET NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT fk_treatments_mkb10');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT FK_4A48CE0D172BA9F8 FOREIGN KEY (mkb10_id) REFERENCES mkb10 (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_treatments_mkb10 RENAME TO IDX_4A48CE0D172BA9F8');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT FK_4A48CE0D172BA9F8');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT fk_treatments_mkb10 FOREIGN KEY (mkb10_id) REFERENCES mkb10 (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_4a48ce0d172ba9f8 RENAME TO idx_treatments_mkb10');
    }
}
