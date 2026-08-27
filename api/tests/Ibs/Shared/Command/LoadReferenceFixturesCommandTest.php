<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Shared\Command;

use Doctrine\DBAL\Connection;
use Ibs\Shared\Command\LoadReferenceFixturesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Tester\CommandTester;

final class LoadReferenceFixturesCommandTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/ref_fixtures_' . uniqid('', true);
        mkdir($this->projectDir . '/fixtures/reference', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->projectDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            $path = $item->getPathname();
            if ($item->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    private function writeFixture(string $table): void
    {
        file_put_contents(
            $this->projectDir . '/fixtures/reference/' . $table . '.sql',
            "INSERT INTO public.{$table} (id) VALUES (1);\n",
        );
    }

    private function tester(Connection $connection): CommandTester
    {
        return new CommandTester(new LoadReferenceFixturesCommand($connection, $this->projectDir));
    }

    public function testLoadsFilesInDependencyOrder(): void
    {
        $ordered = ['drug_groups', 'drugs', 'genetic_markers', 'genetic_marker_values', 'mkb10', 'sms_templates', 'phone_types'];
        foreach ($ordered as $table) {
            $this->writeFixture($table);
        }

        $executed = [];
        $connection = $this->createStub(Connection::class);
        $connection->method('executeStatement')->willReturnCallback(
            static function (string $sql) use (&$executed): int {
                $executed[] = $sql;

                return 1;
            },
        );

        $tester = $this->tester($connection);
        $exitCode = $tester->execute([]);

        self::assertSame(ConsoleCommand::SUCCESS, $exitCode);
        self::assertSame($ordered, $this->extractTables($executed));
        self::assertStringContainsString('Справочники загружены: 7 файл(ов).', $tester->getDisplay());
    }

    public function testWrapsEachFileInItsOwnTransaction(): void
    {
        $this->writeFixture('drug_groups');
        $this->writeFixture('drugs');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))->method('beginTransaction');
        $connection->expects(self::exactly(2))->method('commit');

        $exitCode = $this->tester($connection)->execute([]);

        self::assertSame(ConsoleCommand::SUCCESS, $exitCode);
    }

    public function testLoadsUnknownFilesAlphabeticallyAfterOrderedOnes(): void
    {
        $this->writeFixture('drug_groups');
        $this->writeFixture('zzz_extra');
        $this->writeFixture('aaa_extra');

        $executed = [];
        $connection = $this->createStub(Connection::class);
        $connection->method('executeStatement')->willReturnCallback(
            static function (string $sql) use (&$executed): int {
                $executed[] = $sql;

                return 1;
            },
        );

        $exitCode = $this->tester($connection)->execute([]);

        self::assertSame(ConsoleCommand::SUCCESS, $exitCode);
        self::assertSame(['drug_groups', 'aaa_extra', 'zzz_extra'], $this->extractTables($executed));
    }

    public function testMissingDirectoryReturnsFailure(): void
    {
        $connection = $this->createStub(Connection::class);
        $command = new LoadReferenceFixturesCommand($connection, $this->projectDir . '/nope');

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(ConsoleCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Каталог фикстур не найден', $tester->getDisplay());
    }

    public function testEmptyDirectoryReturnsFailure(): void
    {
        $connection = $this->createStub(Connection::class);

        $tester = $this->tester($connection);
        $exitCode = $tester->execute([]);

        self::assertSame(ConsoleCommand::FAILURE, $exitCode);
        self::assertStringContainsString('В каталоге нет SQL-файлов', $tester->getDisplay());
    }

    public function testSqlErrorRollsBackAndReturnsFailure(): void
    {
        $this->writeFixture('drug_groups');
        $this->writeFixture('drugs');

        $connection = $this->createMock(Connection::class);
        $connection->method('executeStatement')->willThrowException(new \RuntimeException('SQL failure'));
        $connection->expects(self::once())->method('rollBack');

        $tester = $this->tester($connection);
        $exitCode = $tester->execute([]);

        self::assertSame(ConsoleCommand::FAILURE, $exitCode);
        self::assertStringContainsString('Ошибка загрузки', $tester->getDisplay());
    }

    /**
     * @param list<string> $sqls
     *
     * @return list<string>
     */
    private function extractTables(array $sqls): array
    {
        $tables = [];
        foreach ($sqls as $sql) {
            preg_match('/INSERT INTO public\.(\w+)/', $sql, $matches);
            $tables[] = $matches[1] ?? '';
        }

        return $tables;
    }
}
