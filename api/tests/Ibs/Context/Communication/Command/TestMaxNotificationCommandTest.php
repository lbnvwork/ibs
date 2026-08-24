<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TestMaxNotificationCommandTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();

        $application = new Application($client->getKernel());
        $command = $application->find('app:communication:test-max');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testInvalidPatientIdReturnsInvalid(): void
    {
        $exitCode = $this->commandTester->execute(['patient_id' => 'abc', 'chat_id' => 'chat-42']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('patient_id', $this->commandTester->getDisplay());
    }

    public function testEmptyChatIdReturnsInvalid(): void
    {
        $exitCode = $this->commandTester->execute(['patient_id' => '999011', 'chat_id' => '']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('chat_id', $this->commandTester->getDisplay());
    }

    public function testCreatesContactAndReportsFailureForUnavailableChannel(): void
    {
        $exitCode = $this->commandTester->execute([
            'patient_id' => '999011',
            'chat_id' => 'chat-42',
            'message' => 'Привет',
        ]);

        // В тестовом окружении MAX недоступен (URL 'test' не является валидным),
        // поэтому отправка завершается ошибкой без повторов.
        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Отправка завершилась с ошибкой', $this->commandTester->getDisplay());

        // Команда должна была создать контакт пациента перед отправкой.
        $repository = $this->entityManager->getRepository(PatientChannelIdentity::class);
        $contact = $repository->findOneBy(['patientId' => 999011, 'channelType' => 'max']);
        self::assertNotNull($contact);
        self::assertSame('chat-42', $contact->getValue());
    }
}
