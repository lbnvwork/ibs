<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Standardizes the patient_vitals_latest.patient_id unique index name to
 * uniq_patient_id (matching dev; the test DB still had Doctrine's
 * auto-generated uniq_7e8dfed86b899279 from the original migration), so the
 * mapping's explicit UniqueConstraint name matches every environment.
 */
final class Version20260806132847FixPatientVitalsLatestUniqueIndexName extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename patient_vitals_latest unique index to uniq_patient_id where it still uses the auto-generated name.';
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
