<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
        $templates = [
            [
                'code' => 'appointment_dose',
                'body' => 'Ваше МНО - %mno%. С %date% ВАМ НУЖНО ПРИНИМАТЬ %dose% %drug_genitive%. %comment%',
                'description' => 'Назначение: обычная доза',
            ],
            [
                'code' => 'appointment_alternate',
                'body' => 'Ваше МНО - %mno%. С %date% ВАМ НУЖНО ЧЕРЕДОВАТЬ %dose% и %sdose% %drug_genitive%. %comment%',
                'description' => 'Назначение: чередование доз',
            ],
            [
                'code' => 'analysis_result',
                'body' => 'Ваше МНО - %mno%. Дозировку %drug_genitive% оставьте прежней',
                'description' => 'Анализ: доза прежняя',
            ],
        ];

        foreach ($templates as $template) {
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
