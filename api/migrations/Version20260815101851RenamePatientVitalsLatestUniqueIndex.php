<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renames patient_vitals_latest unique index to the mapping's explicit name.
 */
final class Version20260815101851RenamePatientVitalsLatestUniqueIndex extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename patient_vitals_latest unique index to uniq_patient_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX IF EXISTS uniq_7e8dfed86b899279 RENAME TO uniq_patient_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX IF EXISTS uniq_patient_id RENAME TO uniq_7e8dfed86b899279');
    }
}
