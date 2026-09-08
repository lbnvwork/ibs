<?php

declare(strict_types=1);

namespace Ibs\Context\Communication;

/**
 * Каталог MAX-шаблонов (channel = 'max'), засеваемых в notification_templates (задача 3.67).
 *
 * Единый источник текстов для миграции (Version20260908190000SeedNotificationTemplates)
 * и тестов (NotificationTemplateSeedingTest), чтобы тексты не дублировались и не расходились.
 */
final class NotificationTemplateCatalog
{
    /**
     * @var list<array{code: string, body: string, description: string}>
     */
    public const TEMPLATES = [
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
}
