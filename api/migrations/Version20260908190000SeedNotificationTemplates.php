<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ibs\Context\Communication\NotificationTemplateCatalog;

/**
 * Засев MAX-шаблонов в notification_templates (channel = 'max').
 * Идемпотентно: ON CONFLICT (code, channel) DO NOTHING по существующему
 * unique index uniq_notification_template_code_channel.
 */
final class Version20260908190000SeedNotificationTemplates extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Засев MAX-шаблонов notification_templates (appointment_dose, appointment_alternate, analysis_result)';
    }

    public function up(Schema $schema): void
    {
        foreach (NotificationTemplateCatalog::TEMPLATES as $template) {
            $this->addSql(
                'INSERT INTO notification_templates (code, channel, subject_template, body_template, description) '
                . 'VALUES (:code, :channel, NULL, :body, :description) '
                . 'ON CONFLICT (code, channel) DO NOTHING',
                [
                    'code' => $template['code'],
                    'channel' => 'max',
                    'body' => $template['body'],
                    'description' => $template['description'],
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE FROM notification_templates WHERE channel = 'max' AND code IN ('appointment_dose', 'appointment_alternate', 'analysis_result')",
        );
    }
}
