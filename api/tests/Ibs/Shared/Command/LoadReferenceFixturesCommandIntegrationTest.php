<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Shared\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Интеграционный прогон команды через реальный PostgreSQL: проверяет, что
 * multi-statement SQL артефактов (SET …; INSERT …; SELECT setval …) реально
 * исполняется драйвером, а не только через замоканный Connection.
 */
final class LoadReferenceFixturesCommandIntegrationTest extends WebTestCase
{
    private Connection $connection;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->connection = static::getContainer()->get(Connection::class);
        $this->connection->beginTransaction();

        $application = new Application($client->getKernel());
        $this->commandTester = new CommandTester($application->find('app:fixtures:load-reference'));
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        parent::tearDown();
    }

    public function testLoadsReferenceFixturesIntoDatabase(): void
    {
        self::assertSame(0, $this->fetchInt('SELECT count(*) FROM drugs'));

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(ConsoleCommand::SUCCESS, $exitCode);
        self::assertSame(11, $this->fetchInt('SELECT count(*) FROM drugs'));
        self::assertSame(4, $this->fetchInt('SELECT count(*) FROM drug_groups'));
        self::assertSame(6, $this->fetchInt('SELECT count(*) FROM genetic_markers'));
        self::assertSame(15038, $this->fetchInt('SELECT count(*) FROM mkb10'));

        // setval в конце артефакта реально исполнился: последовательность = max(id).
        self::assertSame(11, $this->fetchInt('SELECT last_value FROM drugs_id_seq'));
    }

    public function testUpsertModeDoesNotDuplicateRows(): void
    {
        self::assertSame(ConsoleCommand::SUCCESS, $this->commandTester->execute([]));
        self::assertSame(11, $this->fetchInt('SELECT count(*) FROM drugs'));

        self::assertSame(ConsoleCommand::SUCCESS, $this->commandTester->execute(['--mode' => 'upsert']));
        self::assertSame(11, $this->fetchInt('SELECT count(*) FROM drugs'));
        self::assertSame(15038, $this->fetchInt('SELECT count(*) FROM mkb10'));
    }

    private function fetchInt(string $sql): int
    {
        $value = $this->connection->fetchOne($sql);

        return \is_numeric($value) ? (int) $value : 0;
    }
}
