<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Entity;

use Doctrine\DBAL\Connection;
use Ibs\Context\Communication\NotificationTemplateCatalog;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Идемпотентность засева notification_templates. Тексты берутся из единого каталога
 * NotificationTemplateCatalog (тот же, что использует миграция), чтобы тест замечал
 * расхождение body_template/description при изменении текстов.
 */
final class NotificationTemplateSeedingTest extends KernelTestCase
{
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
        foreach (NotificationTemplateCatalog::TEMPLATES as $template) {
            $this->connection->executeStatement(
                'INSERT INTO notification_templates (code, channel, subject_template, body_template, description) '
                . 'VALUES (?, ?, NULL, ?, ?) ON CONFLICT (code, channel) DO NOTHING',
                [$template['code'], 'max', $template['body'], $template['description']],
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

        foreach (NotificationTemplateCatalog::TEMPLATES as $template) {
            $body = $this->connection->fetchOne(
                'SELECT body_template FROM notification_templates WHERE channel = ? AND code = ?',
                ['max', $template['code']],
            );
            $description = $this->connection->fetchOne(
                'SELECT description FROM notification_templates WHERE channel = ? AND code = ?',
                ['max', $template['code']],
            );

            self::assertSame($template['body'], $body);
            self::assertSame($template['description'], $description);
        }
    }

    private function fetchInt(string $sql): int
    {
        $value = $this->connection->fetchOne($sql);

        return \is_numeric($value) ? (int) $value : 0;
    }
}

