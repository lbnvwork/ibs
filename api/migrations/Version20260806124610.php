<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806124610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification_logs and notification_templates tables for the Communication NotificationService.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification_logs (id SERIAL NOT NULL, patient_id INT DEFAULT NULL, treatment_id INT DEFAULT NULL, channel_type VARCHAR(32) DEFAULT NULL, recipient_address VARCHAR(255) DEFAULT NULL, priority VARCHAR(16) DEFAULT NULL, template_code VARCHAR(64) DEFAULT NULL, status VARCHAR(16) DEFAULT NULL, error_message TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_notification_log_patient_id ON notification_logs (patient_id)');
        $this->addSql('CREATE INDEX idx_notification_log_treatment_id ON notification_logs (treatment_id)');
        $this->addSql('CREATE TABLE notification_templates (id SERIAL NOT NULL, code VARCHAR(128) DEFAULT NULL, channel VARCHAR(16) DEFAULT NULL, subject_template VARCHAR(255) DEFAULT NULL, body_template TEXT DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_notification_template_code_channel ON notification_templates (code, channel)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification_logs');
        $this->addSql('DROP TABLE notification_templates');
    }
}
