<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\Communication\Entity\NotificationTemplate;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Repository\NotificationLogRepository;
use Ibs\Context\Communication\Service\ChannelInterface;
use Ibs\Context\Communication\Service\ChannelRegistry;
use Ibs\Context\Communication\Service\Exception\NotificationDeliveryException;
use Ibs\Context\Communication\Service\MaxChannel;
use Ibs\Context\Communication\Service\NotificationService;
use Ibs\Context\Communication\Service\PatientContactResolver;
use Ibs\Context\Communication\Service\RetrySleeperInterface;
use Ibs\Context\Communication\Service\TemplateResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

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

    private function addContact(int $patientId, string $channelType, string $value): void
    {
        $this->entityManager->persist(
            (new PatientChannelIdentity())
                ->setPatientId($patientId)
                ->setChannelType($channelType)
                ->setValue($value),
        );
        $this->entityManager->flush();
    }

    /**
     * @param iterable<ChannelInterface> $channels
     */
    private function service(iterable $channels, ?RetrySleeperInterface $retrySleeper = null): NotificationService
    {
        return new NotificationService(
            new ChannelRegistry($channels),
            static::getContainer()->get(TemplateResolver::class),
            static::getContainer()->get(PatientContactResolver::class),
            static::getContainer()->get(NotificationLogRepository::class),
            null,
            $retrySleeper ?? new ImmediateRetrySleeper(),
        );
    }

    public function testFullSendCycleWritesNotificationLogToDatabase(): void
    {
        $logRepository = static::getContainer()->get(NotificationLogRepository::class);
        $templateResolver = static::getContainer()->get(TemplateResolver::class);
        $contactResolver = static::getContainer()->get(PatientContactResolver::class);

        $this->entityManager->persist(
            (new PatientChannelIdentity())
                ->setPatientId(999001)
                ->setChannelType('sms')
                ->setValue('+70000000001'),
        );
        $this->entityManager->flush();

        $channel = FakeChannel::succeeding('sms');
        $service = new NotificationService(new ChannelRegistry([$channel]), $templateResolver, $contactResolver, $logRepository);

        $recipient = new Recipient(patientId: 999001);
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
        $contactResolver = static::getContainer()->get(PatientContactResolver::class);

        $this->entityManager->persist(
            (new PatientChannelIdentity())
                ->setPatientId(999002)
                ->setChannelType('sms')
                ->setValue('+70000000002'),
        );
        $this->entityManager->flush();

        $channel = FakeChannel::succeeding('sms');
        $service = new NotificationService(new ChannelRegistry([$channel]), $templateResolver, $contactResolver, $logRepository);

        $recipient = new Recipient(patientId: 999002);
        $message = new NotificationMessage(body: 'ignored', template: 'it_reminder_24h', data: ['patient_name' => 'Пётр']);

        $service->send($recipient, $message, ['sms'], Priority::IMMEDIATE);

        self::assertSame('Здравствуйте, Пётр! Пора измерить МНО.', $channel->calls[0]['message']->body);

        $logs = $service->getHistoryForPatient(999002);
        self::assertCount(1, $logs);
        self::assertSame('sent', $logs[0]->getStatus());
        self::assertSame('it_reminder_24h', $logs[0]->getTemplateCode());
    }

    public function testMissingContactIsSkippedForRoutine(): void
    {
        $channel = FakeChannel::succeeding('sms');
        $service = $this->service([$channel]);

        $recipient = new Recipient(patientId: 999003);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(0, $channel->calls);

        $logs = $service->getHistoryForPatient(999003);
        self::assertCount(1, $logs);
        self::assertSame('failed', $logs[0]->getStatus());
        self::assertStringContainsString('Address not configured for channel "sms".', $logs[0]->getErrorMessage() ?? '');
        self::assertNull($logs[0]->getRecipientAddress());
    }

    public function testMissingContactThrowsForImmediate(): void
    {
        $channel = FakeChannel::succeeding('sms');
        $service = $this->service([$channel]);

        $recipient = new Recipient(patientId: 999004);
        $message = new NotificationMessage(body: 'Критический алерт');

        $this->expectException(NotificationDeliveryException::class);

        try {
            $service->send($recipient, $message, ['sms'], Priority::IMMEDIATE);
        } finally {
            self::assertCount(0, $channel->calls);

            $logs = $service->getHistoryForPatient(999004);
            self::assertCount(1, $logs);
            self::assertSame('failed', $logs[0]->getStatus());
        }
    }

    public function testMultipleChannelsResolveDifferentAddresses(): void
    {
        $this->addContact(999005, 'sms', '+70000000005');
        $this->addContact(999005, 'email', 'patient-999005@example.test');

        $sms = FakeChannel::succeeding('sms');
        $email = FakeChannel::succeeding('email');
        $service = $this->service([$sms, $email]);

        $recipient = new Recipient(patientId: 999005);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['sms', 'email'], Priority::ROUTINE);

        self::assertCount(1, $sms->calls);
        self::assertCount(1, $email->calls);
        self::assertSame('+70000000005', $sms->calls[0]['address']);
        self::assertSame('patient-999005@example.test', $email->calls[0]['address']);

        $logs = $service->getHistoryForPatient(999005);
        self::assertCount(2, $logs);

        $addresses = array_map(static fn ($log) => $log->getRecipientAddress(), $logs);
        self::assertContains('+70000000005', $addresses);
        self::assertContains('patient-999005@example.test', $addresses);
    }

    public function testSuccessPersistsExternalIdAndRecipientAddress(): void
    {
        $this->addContact(999006, 'sms', '+70000000006');
        $service = $this->service([FakeChannel::succeedingWithExternalId('sms', 'msg-123')]);

        $recipient = new Recipient(patientId: 999006);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        $logs = $service->getHistoryForPatient(999006);
        self::assertCount(1, $logs);
        self::assertSame('sent', $logs[0]->getStatus());
        self::assertSame('msg-123', $logs[0]->getExternalId());
        self::assertSame('+70000000006', $logs[0]->getRecipientAddress());
    }

    public function testChannelFailureIsLoggedForRoutine(): void
    {
        $this->addContact(999007, 'sms', '+70000000007');
        $service = $this->service([FakeChannel::failing('sms', 'Provider timeout')]);

        $recipient = new Recipient(patientId: 999007);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        $logs = $service->getHistoryForPatient(999007);
        self::assertCount(1, $logs);
        self::assertSame('failed', $logs[0]->getStatus());
        self::assertSame('Provider timeout', $logs[0]->getErrorMessage());
    }

    public function testChannelFailureThrowsForImmediate(): void
    {
        $this->addContact(999008, 'sms', '+70000000008');
        $service = $this->service([FakeChannel::failing('sms', 'Provider timeout')]);

        $recipient = new Recipient(patientId: 999008);
        $message = new NotificationMessage(body: 'Критический алерт');

        $this->expectException(NotificationDeliveryException::class);

        try {
            $service->send($recipient, $message, ['sms'], Priority::IMMEDIATE);
        } finally {
            $logs = $service->getHistoryForPatient(999008);
            self::assertCount(1, $logs);
            self::assertSame('failed', $logs[0]->getStatus());
        }
    }

    public function testUnavailableChannelIsSkippedAndLoggedForRoutine(): void
    {
        $channel = FakeChannel::unavailable('sms');
        $service = $this->service([$channel]);

        $recipient = new Recipient(patientId: 999009);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(0, $channel->calls);

        $logs = $service->getHistoryForPatient(999009);
        self::assertCount(1, $logs);
        self::assertSame('failed', $logs[0]->getStatus());
        self::assertSame('Channel "sms" is not available.', $logs[0]->getErrorMessage());
    }

    public function testRetriesTemporaryFailuresWithImmediateSleeper(): void
    {
        $this->addContact(999010, 'sms', '+70000000010');
        $channel = FakeChannel::failingRetryable('sms', 'Temporary error');
        $service = $this->service([$channel], new ImmediateRetrySleeper());

        $recipient = new Recipient(patientId: 999010);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(4, $channel->calls);

        $logs = $service->getHistoryForPatient(999010);
        self::assertCount(1, $logs);
        self::assertSame('failed', $logs[0]->getStatus());
    }

    public function testMaxChannelSendWritesMaxLogWithExternalId(): void
    {
        $this->addContact(999011, 'max', 'chat-42');

        $httpClient = new MockHttpClient(
            new MockResponse(
                json_encode(['message_id' => 'max-123', 'status' => 'sent'], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            ),
        );

        $maxChannel = new MaxChannel($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $service = $this->service([$maxChannel], new ImmediateRetrySleeper());

        $recipient = new Recipient(patientId: 999011);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['max'], Priority::ROUTINE);

        $logs = $service->getHistoryForPatient(999011);
        self::assertCount(1, $logs);
        self::assertSame('max', $logs[0]->getChannelType());
        self::assertSame('sent', $logs[0]->getStatus());
        self::assertSame('max-123', $logs[0]->getExternalId());
        self::assertSame('chat-42', $logs[0]->getRecipientAddress());
    }

    public function testMaxChannelIntegrationWithNotificationService(): void
    {
        $this->addContact(999011, 'max', 'chat-999011');

        $captured = null;
        $httpClient = new MockHttpClient(
            static function (string $method, string $url, array $options = []) use (&$captured): MockResponse {
                $captured = ['method' => $method, 'url' => $url];

                return new MockResponse(
                    json_encode(['message_id' => 'max-msg-123', 'status' => 'sent'], JSON_THROW_ON_ERROR),
                    ['http_code' => 200],
                );
            },
        );

        $maxChannel = new MaxChannel($httpClient, 'https://platform-api2.max.ru', 'test-token');
        $service = $this->service([$maxChannel]);

        $recipient = new Recipient(patientId: 999011);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['max'], Priority::ROUTINE);

        // Реальный MaxChannel отправил POST на /messages с нужным chat_id.
        self::assertNotNull($captured);
        self::assertSame('POST', $captured['method']);
        self::assertSame('/messages', parse_url($captured['url'], PHP_URL_PATH));
        parse_str((string) parse_url($captured['url'], PHP_URL_QUERY), $query);
        self::assertSame('chat-999011', $query['chat_id'] ?? null);

        $logs = $service->getHistoryForPatient(999011);
        self::assertCount(1, $logs);
        self::assertSame('sent', $logs[0]->getStatus());
        self::assertSame('max', $logs[0]->getChannelType());
        self::assertSame('max-msg-123', $logs[0]->getExternalId());
        self::assertSame('chat-999011', $logs[0]->getRecipientAddress());
    }
}
