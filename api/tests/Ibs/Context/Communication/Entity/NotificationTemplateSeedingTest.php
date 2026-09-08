<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Entity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Идемпотентность засева notification_templates (те же INSERT, что и в миграции
 * Version20260908190000SeedNotificationTemplates).
 */
final class NotificationTemplateSeedingTest extends KernelTestCase
{
    /** @var list<array{0: string, 1: string}> [code, body] */
    private const TEMPLATES = [
        ['appointment_dose', 'Ваше МНО - %mno%. С %date% ВАМ НУЖНО ПРИНИМАТЬ %dose% %drug_genitive%. %comment%'],
        ['appointment_alternate', 'Ваше МНО - %mno%. С %date% ВАМ НУЖНО ЧЕРЕДОВАТЬ %dose% и %sdose% %drug_genitive%. %comment%'],
        ['analysis_result', 'Ваше МНО - %mno%. Дозировку %drug_genitive% оставьте прежней'],
    ];

    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = static::getContainer()->get(Connection::class);
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        parent::tearDown();
    }

    private function seed(): void
    {
        foreach (self::TEMPLATES as [$code, $body]) {
            $this->connection->executeStatement(
                'INSERT INTO notification_templates (code, channel, subject_template, body_template, description) '
                . 'VALUES (?, ?, NULL, ?, ?) ON CONFLICT (code, channel) DO NOTHING',
                [$code, 'max', $body, ''],
            );
        }
    }

    public function testSeedingIsIdempotent(): void
    {
        $this->seed();
        $this->seed();

        $count = $this->fetchInt("SELECT count(*) FROM notification_templates WHERE channel = 'max'");

        self::assertSame(3, $count);
    }

    public function testSeededCodesMatchContract(): void
    {
        $this->seed();

        $codes = $this->connection->fetchFirstColumn(
            "SELECT code FROM notification_templates WHERE channel = 'max' ORDER BY code",
        );

        self::assertSame(['analysis_result', 'appointment_alternate', 'appointment_dose'], $codes);
    }

    private function fetchInt(string $sql): int
    {
        $value = $this->connection->fetchOne($sql);

        return \is_numeric($value) ? (int) $value : 0;
    }
}
