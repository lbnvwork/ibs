<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Entity\NotificationTemplate;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Repository\NotificationTemplateRepository;
use Ibs\Context\Communication\Service\Exception\TemplateNotFoundException;
use Ibs\Context\Communication\Service\TemplateResolver;
use PHPUnit\Framework\TestCase;

final class MaxNotificationTemplatesTest extends TestCase
{
    private function resolverReturning(?NotificationTemplate $template): TemplateResolver
    {
        $repository = $this->createStub(NotificationTemplateRepository::class);
        $repository->method('findOneByCodeAndChannel')->willReturn($template);

        return new TemplateResolver($repository);
    }

    public function testAppointmentDoseTemplateIsResolved(): void
    {
        $template = (new NotificationTemplate('appointment_dose', 'max'))
            ->setBodyTemplate('Ваше МНО - %mno%. С %date% ВАМ НУЖНО ПРИНИМАТЬ %dose% %drug_genitive%. %comment%');

        $resolved = $this->resolverReturning($template)->resolve(
            new NotificationMessage(
                body: 'ignored',
                template: 'appointment_dose',
                data: [
                    'mno' => '2.4',
                    'date' => '10.09.2026',
                    'dose' => '2.5',
                    'drug_genitive' => 'варфарина',
                    'comment' => 'Контроль 17.09.2026',
                ],
            ),
            'max',
        );

        self::assertSame(
            'Ваше МНО - 2.4. С 10.09.2026 ВАМ НУЖНО ПРИНИМАТЬ 2.5 варфарина. Контроль 17.09.2026',
            $resolved->body,
        );
    }

    public function testAppointmentAlternateTemplateIsResolved(): void
    {
        $template = (new NotificationTemplate('appointment_alternate', 'max'))
            ->setBodyTemplate('Ваше МНО - %mno%. С %date% ВАМ НУЖНО ЧЕРЕДОВАТЬ %dose% и %sdose% %drug_genitive%. %comment%');

        $resolved = $this->resolverReturning($template)->resolve(
            new NotificationMessage(
                body: 'ignored',
                template: 'appointment_alternate',
                data: [
                    'mno' => '2.4',
                    'date' => '10.09.2026',
                    'dose' => '2.5',
                    'sdose' => '2.75',
                    'drug_genitive' => 'варфарина',
                    'comment' => 'Контроль 17.09.2026',
                ],
            ),
            'max',
        );

        self::assertSame(
            'Ваше МНО - 2.4. С 10.09.2026 ВАМ НУЖНО ЧЕРЕДОВАТЬ 2.5 и 2.75 варфарина. Контроль 17.09.2026',
            $resolved->body,
        );
    }

    public function testAnalysisResultTemplateIsResolved(): void
    {
        $template = (new NotificationTemplate('analysis_result', 'max'))
            ->setBodyTemplate('Ваше МНО - %mno%. Дозировку %drug_genitive% оставьте прежней');

        $resolved = $this->resolverReturning($template)->resolve(
            new NotificationMessage(
                body: 'ignored',
                template: 'analysis_result',
                data: ['mno' => '2.4', 'drug_genitive' => 'варфарина'],
            ),
            'max',
        );

        self::assertSame('Ваше МНО - 2.4. Дозировку варфарина оставьте прежней', $resolved->body);
    }

    public function testUnknownTemplateCodeThrows(): void
    {
        $this->expectException(TemplateNotFoundException::class);

        $this->resolverReturning(null)->resolve(
            new NotificationMessage(body: 'ignored', template: 'unknown_code'),
            'max',
        );
    }

    public function testTemplateSeededOnlyForMaxChannelThrowsOnSms(): void
    {
        $this->expectException(TemplateNotFoundException::class);

        $this->resolverReturning(null)->resolve(
            new NotificationMessage(body: 'ignored', template: 'appointment_dose'),
            'sms',
        );
    }

    public function testMissingPlaceholderStaysIntact(): void
    {
        $template = (new NotificationTemplate('analysis_result', 'max'))
            ->setBodyTemplate('Ваше МНО - %mno%. Дозировку %drug_genitive% оставьте прежней');

        $resolved = $this->resolverReturning($template)->resolve(
            new NotificationMessage(body: 'ignored', template: 'analysis_result', data: ['mno' => '2.4']),
            'max',
        );

        self::assertSame('Ваше МНО - 2.4. Дозировку %drug_genitive% оставьте прежней', $resolved->body);
    }
}
