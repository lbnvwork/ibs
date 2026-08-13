<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\NotificationTemplate;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Repository\NotificationLogRepository;
use Ibs\Context\Communication\Service\ChannelRegistry;
use Ibs\Context\Communication\Service\NotificationService;
use Ibs\Context\Communication\Service\TemplateResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Полный цикл отправки через NotificationService с реальным TemplateResolver/
 * NotificationLogRepository (реальная БД) и тестовым адаптером канала.
 */
class NotificationServiceIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
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

    public function testFullSendCycleWritesNotificationLogToDatabase(): void
    {
        $logRepository = static::getContainer()->get(NotificationLogRepository::class);
        $templateResolver = static::getContainer()->get(TemplateResolver::class);

        $channel = FakeChannel::succeeding('sms');
        $service = new NotificationService(new ChannelRegistry([$channel]), $templateResolver, $logRepository);

        $recipient = new Recipient(patientId: 999001, phone: '+70000000001');
        $message = new NotificationMessage(body: 'Интеграционный тест уведомления');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(1, $channel->calls);

        $logs = $service->getHistoryForPatient(999001);
        self::assertCount(1, $logs);
        self::assertSame('sent', $logs[0]->getStatus());
        self::assertSame('sms', $logs[0]->getChannelType());
    }

    public function testFullSendCycleResolvesRealTemplateFromDatabase(): void
    {
        $template = (new NotificationTemplate('it_reminder_24h', 'sms'))
            ->setBodyTemplate('Здравствуйте, %patient_name%! Пора измерить МНО.');
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $logRepository = static::getContainer()->get(NotificationLogRepository::class);
        $templateResolver = static::getContainer()->get(TemplateResolver::class);

        $channel = FakeChannel::succeeding('sms');
        $service = new NotificationService(new ChannelRegistry([$channel]), $templateResolver, $logRepository);

        $recipient = new Recipient(patientId: 999002, phone: '+70000000002');
        $message = new NotificationMessage(body: 'ignored', template: 'it_reminder_24h', data: ['patient_name' => 'Пётр']);

        $service->send($recipient, $message, ['sms'], Priority::IMMEDIATE);

        self::assertSame('Здравствуйте, Пётр! Пора измерить МНО.', $channel->calls[0]['message']->body);

        $logs = $service->getHistoryForPatient(999002);
        self::assertCount(1, $logs);
        self::assertSame('sent', $logs[0]->getStatus());
        self::assertSame('it_reminder_24h', $logs[0]->getTemplateCode());
    }
}
