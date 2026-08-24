<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Service\Message\SendNotificationEnvelope;
use Ibs\Context\Communication\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * Проверяет реальную маршрутизацию Messenger: ROUTINE-приоритет должен
 * попадать в async-транспорт (и ждать обработчика), а не выполняться
 * синхронно в том же запросе.
 */
class NotificationServiceMessengerRoutingTest extends KernelTestCase
{
    public function testRoutineNotificationIsQueuedOnAsyncTransportNotHandledInline(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $service = $container->get(NotificationService::class);

        $recipient = new Recipient(patientId: 555555);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        $sent = $transport->getSent();
        self::assertCount(1, $sent);
        self::assertInstanceOf(SendNotificationEnvelope::class, $sent[0]->getMessage());

        // Ничего не обработало сообщение синхронно — история пуста, пока очередь не будет вычитана воркером.
        self::assertCount(0, $service->getHistoryForPatient(555555));
    }

    public function testRoutineEnvelopeContainsExpectedData(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        $service = $container->get(NotificationService::class);

        $recipient = new Recipient(patientId: 555555);
        $message = new NotificationMessage(body: 'Напоминание о приёме');

        $service->send($recipient, $message, ['sms'], Priority::ROUTINE);

        $sent = $transport->getSent();
        self::assertCount(1, $sent);

        /** @var SendNotificationEnvelope $envelope */
        $envelope = $sent[0]->getMessage();
        self::assertInstanceOf(SendNotificationEnvelope::class, $envelope);
        self::assertSame(555555, $envelope->recipient->patientId);
        self::assertSame(['sms'], $envelope->channels);
        self::assertSame(Priority::ROUTINE, $envelope->priority);
        self::assertSame('Напоминание о приёме', $envelope->message->body);
    }
}
