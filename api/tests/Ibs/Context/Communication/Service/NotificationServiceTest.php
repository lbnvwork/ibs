<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Entity\NotificationLog;
use Ibs\Context\Communication\Entity\PatientChannelIdentity;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\SendResult;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Repository\NotificationLogRepository;
use Ibs\Context\Communication\Repository\NotificationTemplateRepository;
use Ibs\Context\Communication\Repository\PatientChannelIdentityRepository;
use Ibs\Context\Communication\Service\PatientContactResolver;
use Ibs\Context\Communication\Service\ChannelRegistry;
use Ibs\Context\Communication\Service\RetrySleeperInterface;
use Ibs\Context\Communication\Service\Exception\ChannelNotFoundException;
use Ibs\Context\Communication\Service\Exception\NoChannelsConfiguredException;
use Ibs\Context\Communication\Service\Exception\NotificationDeliveryException;
use Ibs\Context\Communication\Service\Message\SendNotificationEnvelope;
use Ibs\Context\Communication\Service\NotificationService;
use Ibs\Context\Communication\Service\TemplateResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationServiceTest extends TestCase
{
    /** @var NotificationLog[] */
    private array $savedLogs;

    private int $flushCount;

    private NotificationLogRepository $logRepository;

    protected function setUp(): void
    {
        $this->savedLogs = [];
        $this->flushCount = 0;

        $this->logRepository = $this->createStub(NotificationLogRepository::class);
        $this->logRepository->method('save')
            ->willReturnCallback(function (NotificationLog $log): void {
                $this->savedLogs[] = $log;
            });
        $this->logRepository->method('flush')
            ->willReturnCallback(function (): void {
                $this->flushCount++;
            });
    }

    private function templateResolver(): TemplateResolver
    {
        $templates = $this->createStub(NotificationTemplateRepository::class);
        $templates->method('findOneByCodeAndChannel')->willReturn(null);

        return new TemplateResolver($templates);
    }

    /**
     * @param array<string, string> $addresses карта «канал => адрес»
     */
    private function contactResolver(array $addresses = []): PatientContactResolver
    {
        $repository = $this->createStub(PatientChannelIdentityRepository::class);
        $repository->method('findOneByPatientAndChannel')
            ->willReturnCallback(function (int $patientId, string $channelType) use ($addresses): ?PatientChannelIdentity {
                $address = $addresses[$channelType] ?? null;
                if (null === $address) {
                    return null;
                }

                return (new PatientChannelIdentity())
                    ->setPatientId($patientId)
                    ->setChannelType($channelType)
                    ->setValue($address);
            });

        return new PatientContactResolver($repository);
    }

    private function newService(
        iterable $channels,
        ?MessageBusInterface $messageBus = null,
        ?NotificationLogRepository $logRepository = null,
        ?PatientContactResolver $contactResolver = null,
        ?RetrySleeperInterface $retrySleeper = null,
    ): NotificationService {
        return new NotificationService(
            new ChannelRegistry($channels),
            $this->templateResolver(),
            $contactResolver ?? $this->contactResolver(),
            $logRepository ?? $this->logRepository,
            $messageBus,
            $retrySleeper ?? new ImmediateRetrySleeper(),
        );
    }

    /**
     * TC-3.2-1: ROUTINE + очередь недоступна -> синхронная отправка через SmsChannel,
     * в NotificationLog создаётся запись со статусом 'sent'.
     */
    public function testSendRoutineWithoutMessenger(): void
    {
        $sms = FakeChannel::succeeding('sms');
        $service = $this->newService([$sms], messageBus: null, contactResolver: $this->contactResolver(['sms' => '+70000000000']));

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Не забудьте принять лекарство');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(1, $sms->calls);
        self::assertSame('+70000000000', $sms->calls[0]['address']);
        self::assertCount(1, $this->savedLogs);
        self::assertSame('sent', $this->savedLogs[0]->getStatus());
        self::assertSame('sms', $this->savedLogs[0]->getChannelType());
        self::assertSame(Priority::ROUTINE->value, $this->savedLogs[0]->getPriority());
        self::assertSame(1, $this->savedLogs[0]->getPatientId());
        self::assertSame('+70000000000', $this->savedLogs[0]->getRecipientAddress());
    }

    public function testSendLogsFailedWhenAddressNotConfiguredForRoutine(): void
    {
        $sms = FakeChannel::succeeding('sms');
        $service = $this->newService([$sms], messageBus: null, contactResolver: $this->contactResolver([]));

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Не забудьте принять лекарство');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(0, $sms->calls);
        self::assertCount(1, $this->savedLogs);
        self::assertSame('failed', $this->savedLogs[0]->getStatus());
        self::assertStringContainsString('Address not configured for channel "sms".', $this->savedLogs[0]->getErrorMessage());
        self::assertNull($this->savedLogs[0]->getRecipientAddress());
    }

    public function testImmediateRejectsWhenAddressNotConfiguredForChannel(): void
    {
        $sms = FakeChannel::succeeding('sms');
        $service = $this->newService([$sms], contactResolver: $this->contactResolver([]));

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Критический алерт');

        $this->expectException(NotificationDeliveryException::class);

        try {
            $service->send($recipient, $message, ['sms'], Priority::IMMEDIATE);
        } finally {
            self::assertCount(0, $sms->calls);
            self::assertSame('failed', $this->savedLogs[0]->getStatus());
            self::assertStringContainsString('Address not configured for channel "sms".', $this->savedLogs[0]->getErrorMessage());
        }
    }

    /**
     * TC-3.2-2: пустой список каналов + IMMEDIATE -> NoChannelsConfiguredException,
     * ошибка фиксируется в логе.
     */
    public function testSendWithNoChannelsThrowsException(): void
    {
        $service = $this->newService([]);

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Критический алерт');

        $this->expectException(NoChannelsConfiguredException::class);

        try {
            $service->send($recipient, $message, [], Priority::IMMEDIATE);
        } finally {
            self::assertCount(1, $this->savedLogs);
            self::assertSame('failed', $this->savedLogs[0]->getStatus());
            self::assertNotNull($this->savedLogs[0]->getErrorMessage());
        }
    }

    /**
     * TC-3.2-3: несколько каналов (['push', 'email']) -> отправка в каждый канал
     * последовательно, отдельная запись лога на каждый канал.
     */
    public function testSendToMultipleChannelsLogsEachSeparately(): void
    {
        $push = FakeChannel::succeeding('push');
        $email = FakeChannel::succeeding('email');
        $service = $this->newService(
            [$push, $email],
            contactResolver: $this->contactResolver([
                'push' => 'push-token-123',
                'email' => 'patient@example.test',
            ]),
        );

        $recipient = new Recipient(patientId: 5);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['push', 'email'], Priority::ROUTINE);

        self::assertCount(1, $push->calls);
        self::assertCount(1, $email->calls);
        self::assertSame('push-token-123', $push->calls[0]['address']);
        self::assertSame('patient@example.test', $email->calls[0]['address']);
        self::assertCount(2, $this->savedLogs);

        $channelTypes = array_map(static fn (NotificationLog $log) => $log->getChannelType(), $this->savedLogs);
        self::assertSame(['push', 'email'], $channelTypes);
        foreach ($this->savedLogs as $log) {
            self::assertSame('sent', $log->getStatus());
        }

        // Один flush на оба канала, а не по одному на запись лога.
        self::assertSame(1, $this->flushCount);
    }

    /**
     * TC-3.2-4: шаблон с code='reminder_24h' -> TemplateResolver подставляет
     * плейсхолдеры, итоговый текст уходит в канал.
     */
    public function testSendResolvesTemplateBeforeHandingToChannel(): void
    {
        $sms = FakeChannel::succeeding('sms');

        $templates = $this->createMock(NotificationTemplateRepository::class);
        $template = (new \Ibs\Context\Communication\Entity\NotificationTemplate('reminder_24h', 'sms'))
            ->setBodyTemplate('Здравствуйте, %patient_name%! Пора измерить МНО.');
        $templates->expects(self::once())
            ->method('findOneByCodeAndChannel')
            ->with('reminder_24h', 'sms')
            ->willReturn($template);

        $service = new NotificationService(
            new ChannelRegistry([$sms]),
            new TemplateResolver($templates),
            $this->contactResolver(['sms' => '+70000000000']),
            $this->logRepository,
        );

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'ignored', template: 'reminder_24h', data: ['patient_name' => 'Иван']);

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(1, $sms->calls);
        self::assertSame('Здравствуйте, Иван! Пора измерить МНО.', $sms->calls[0]['message']->body);
        self::assertSame('sent', $this->savedLogs[0]->getStatus());
    }

    public function testImmediatePriorityRethrowsChannelFailure(): void
    {
        $sms = FakeChannel::failing('sms', 'Provider timeout');
        $service = $this->newService([$sms], contactResolver: $this->contactResolver(['sms' => '+70000000000']));

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Критический алерт');

        $this->expectException(NotificationDeliveryException::class);

        try {
            $service->send($recipient, $message, ['sms'], Priority::IMMEDIATE);
        } finally {
            self::assertSame('failed', $this->savedLogs[0]->getStatus());
            self::assertSame('Provider timeout', $this->savedLogs[0]->getErrorMessage());
            // flush() всё равно должен сработать, несмотря на исключение из deliver().
            self::assertSame(1, $this->flushCount);
        }
    }

    public function testRoutinePriorityDoesNotRethrowChannelFailure(): void
    {
        $sms = FakeChannel::failing('sms', 'Provider timeout');
        $service = $this->newService([$sms], contactResolver: $this->contactResolver(['sms' => '+70000000000']));

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        // не должно бросить исключение, несмотря на неудачу канала
        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertSame('failed', $this->savedLogs[0]->getStatus());
    }

    public function testChannelExceptionIsCaughtAndLoggedAsFailure(): void
    {
        $sms = FakeChannel::succeeding('sms')->throwing(new \RuntimeException('Connection refused'));
        $service = $this->newService([$sms], contactResolver: $this->contactResolver(['sms' => '+70000000000']));

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertSame('failed', $this->savedLogs[0]->getStatus());
        self::assertSame('Connection refused', $this->savedLogs[0]->getErrorMessage());
    }

    public function testUnavailableChannelIsSkippedAndLoggedForRoutine(): void
    {
        $sms = FakeChannel::unavailable('sms');
        $service = $this->newService([$sms]);

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        // send() на самом канале не должен вызываться для заведомо недоступного канала.
        self::assertCount(0, $sms->calls);
        self::assertSame('failed', $this->savedLogs[0]->getStatus());
        self::assertSame('Channel "sms" is not available.', $this->savedLogs[0]->getErrorMessage());
    }

    public function testUnavailableChannelThrowsForImmediate(): void
    {
        $sms = FakeChannel::unavailable('sms');
        $service = $this->newService([$sms]);

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Критический алерт');

        $this->expectException(NotificationDeliveryException::class);

        try {
            $service->send($recipient, $message, ['sms'], Priority::IMMEDIATE);
        } finally {
            self::assertCount(0, $sms->calls);
            self::assertSame('failed', $this->savedLogs[0]->getStatus());
            self::assertSame(1, $this->flushCount);
        }
    }

    public function testUnregisteredChannelIsLoggedAndThrowsWhenImmediate(): void
    {
        $service = $this->newService([]);

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Критический алерт');

        $this->expectException(ChannelNotFoundException::class);

        try {
            $service->send($recipient, $message, ['sms'], Priority::IMMEDIATE);
        } finally {
            self::assertSame('failed', $this->savedLogs[0]->getStatus());
            self::assertSame('sms', $this->savedLogs[0]->getChannelType());
        }
    }

    public function testRoutineDispatchesToMessageBusWhenAvailable(): void
    {
        $sms = FakeChannel::succeeding('sms');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(SendNotificationEnvelope::class))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $service = $this->newService([$sms], $messageBus);

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        // Отправка делегирована шине сообщений, канал напрямую не вызывается.
        self::assertCount(0, $sms->calls);
        self::assertCount(0, $this->savedLogs);
    }

    public function testHandleEnvelopeDeliversWithoutRethrowing(): void
    {
        $sms = FakeChannel::failing('sms', 'Provider timeout');
        $service = $this->newService([$sms], contactResolver: $this->contactResolver(['sms' => '+70000000000']));

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');
        $envelope = new SendNotificationEnvelope($recipient, $message, ['sms'], Priority::ROUTINE);

        $service->handleEnvelope($envelope);

        self::assertCount(1, $sms->calls);
        self::assertSame('failed', $this->savedLogs[0]->getStatus());
    }

    public function testRetriesTemporaryFailuresUpToThreeTimes(): void
    {
        $sms = FakeChannel::failingRetryable('sms', 'Temporary error');
        $retrySleeper = new ImmediateRetrySleeper();
        $service = $this->newService(
            [$sms],
            messageBus: null,
            contactResolver: $this->contactResolver(['sms' => '+70000000000']),
            retrySleeper: $retrySleeper,
        );

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(4, $sms->calls);
        self::assertSame([0, 1, 2], $retrySleeper->waitedRetryNumbers);
        self::assertSame('failed', $this->savedLogs[0]->getStatus());
        self::assertSame('Temporary error', $this->savedLogs[0]->getErrorMessage());
    }

    public function testDoesNotRetryCriticalFailures(): void
    {
        $sms = FakeChannel::failing('sms', 'Critical error');
        $retrySleeper = new ImmediateRetrySleeper();
        $service = $this->newService(
            [$sms],
            messageBus: null,
            contactResolver: $this->contactResolver(['sms' => '+70000000000']),
            retrySleeper: $retrySleeper,
        );

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(1, $sms->calls);
        self::assertSame([], $retrySleeper->waitedRetryNumbers);
        self::assertSame('failed', $this->savedLogs[0]->getStatus());
    }

    public function testRetriesUntilSuccess(): void
    {
        $sms = FakeChannel::succeeding('sms')
            ->withResults(
                SendResult::failure('Temporary error', retryable: true),
                SendResult::success(),
            );
        $service = $this->newService(
            [$sms],
            messageBus: null,
            contactResolver: $this->contactResolver(['sms' => '+70000000000']),
            retrySleeper: new ImmediateRetrySleeper(),
        );

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(2, $sms->calls);
        self::assertSame('sent', $this->savedLogs[0]->getStatus());
    }

    public function testLogsExternalIdOnSuccess(): void
    {
        $sms = FakeChannel::succeedingWithExternalId('sms', 'msg-123');
        $service = $this->newService(
            [$sms],
            messageBus: null,
            contactResolver: $this->contactResolver(['sms' => '+70000000000']),
        );

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertSame('msg-123', $this->savedLogs[0]->getExternalId());
    }

    public function testRoutineUnregisteredChannelSkipsAndLogs(): void
    {
        $service = $this->newService([]);

        $recipient = new Recipient(patientId: 1);
        $message = new NotificationMessage(body: 'Напоминание');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        self::assertCount(1, $this->savedLogs);
        self::assertSame('failed', $this->savedLogs[0]->getStatus());
        self::assertStringContainsString('Channel "sms" is not registered.', $this->savedLogs[0]->getErrorMessage());
    }

    public function testGetHistoryForPatientDelegatesToRepository(): void
    {
        $expected = [new NotificationLog()];
        $logRepository = $this->createMock(NotificationLogRepository::class);
        $logRepository->expects(self::once())
            ->method('findByPatient')
            ->with(42)
            ->willReturn($expected);

        $service = $this->newService([], logRepository: $logRepository);

        self::assertSame($expected, $service->getHistoryForPatient(42));
    }

    public function testGetHistoryForTreatmentDelegatesToRepository(): void
    {
        $expected = [new NotificationLog()];
        $logRepository = $this->createMock(NotificationLogRepository::class);
        $logRepository->expects(self::once())
            ->method('findByTreatment')
            ->with(7)
            ->willReturn($expected);

        $service = $this->newService([], logRepository: $logRepository);

        self::assertSame($expected, $service->getHistoryForTreatment(7));
    }
}
