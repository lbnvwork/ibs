<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328105037AlterTreatmentsAddFKwithMkb10 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mkb10_id column to treatments table and set up foreign key relationship with mkb10 table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE treatments ADD mkb10_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE treatments ADD CONSTRAINT FK_TREATMENTS_MKB10 FOREIGN KEY (mkb10_id) REFERENCES mkb10 (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_TREATMENTS_MKB10 ON treatments (mkb10_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE treatments DROP CONSTRAINT FK_TREATMENTS_MKB10');
        $this->addSql('DROP INDEX IDX_TREATMENTS_MKB10');
        $this->addSql('ALTER TABLE treatments DROP mkb10_id');
    }
}
