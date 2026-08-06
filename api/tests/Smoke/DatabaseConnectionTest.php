<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseConnectionTest extends KernelTestCase
{
    public function testEntityManagerIsAvailableFromContainer(): void
    {
        self::bootKernel();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testDatabaseConnectionCanExecuteSimpleQuery(): void
    {
        self::bootKernel();

        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $result = $connection->executeQuery('SELECT 1')->fetchOne();

        $this->assertEquals('1', (string) $result);
    }

    public function testRunningAgainstDedicatedTestDatabase(): void
    {
        self::bootKernel();

        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $dbName = $connection->executeQuery('SELECT current_database()')->fetchOne();

        // Guard against ever accidentally running the suite against the dev database.
        $this->assertStringEndsWith('_test', (string) $dbName);
    }
}
