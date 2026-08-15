<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds patient_channel_identities table and external_id column to notification_logs.
 */
final class Version20260814130000AddPatientChannelIdentitiesAndExternalId extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add patient_channel_identities table and external_id to notification_logs';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE patient_channel_identities (id SERIAL NOT NULL, patient_id INT NOT NULL, channel_type VARCHAR(32) NOT NULL, value VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_patient_channel_identity ON patient_channel_identities (patient_id, channel_type)');
        $this->addSql('ALTER TABLE notification_logs ADD external_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE patient_channel_identities');
        $this->addSql('ALTER TABLE notification_logs DROP external_id');
    }
}
