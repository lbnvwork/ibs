<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Entity\NotificationLog;
use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Priority;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Model\SendResult;
use Ibs\Context\Communication\Repository\NotificationLogRepository;
use Ibs\Context\Communication\Service\Exception\ChannelNotFoundException;
use Ibs\Context\Communication\Service\Exception\NoChannelsConfiguredException;
use Ibs\Context\Communication\Service\Exception\NotificationDeliveryException;
use Ibs\Context\Communication\Service\Message\SendNotificationEnvelope;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Единая точка отправки уведомлений через любые каналы связи (SMS, Push, Email, MAX).
 * Скрывает от клиентов детали конкретных каналов, применяет приоритет доставки
 * и логирует каждую попытку отправки в NotificationLog.
 */
final class NotificationService
{
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly TemplateResolver $templateResolver,
        private readonly NotificationLogRepository $logRepository,
        private readonly ?MessageBusInterface $messageBus = null,
        private readonly RetrySleeperInterface $retrySleeper = new ExponentialRetrySleeper(),
    ) {
    }

    /**
     * @param string[] $channels типы каналов, например ['sms', 'email']
     */
    public function send(
        Recipient $recipient,
        NotificationMessage $message,
        array $channels = [],
        Priority $priority = Priority::ROUTINE,
    ): void {
        if ([] === $channels) {
            $exception = new NoChannelsConfiguredException();
            $this->log($recipient, null, $priority, $message->template, 'failed', $exception->getMessage());
            throw $exception;
        }

        if (Priority::IMMEDIATE === $priority) {
            // Критические алерты: отправляем синхронно, ошибка пробрасывается вызывающему коду.
            $this->deliver($recipient, $message, $channels, $priority, rethrow: true);
            return;
        }

        if (null !== $this->messageBus) {
            $this->messageBus->dispatch(new SendNotificationEnvelope($recipient, $message, $channels, $priority));
            return;
        }

        // Очередь недоступна — fallback на синхронную отправку, ошибки только логируются.
        $this->deliver($recipient, $message, $channels, $priority, rethrow: false);
    }

    /**
     * Обработчик асинхронной доставки ROUTINE-уведомлений через Messenger.
     */
    #[AsMessageHandler]
    public function handleEnvelope(SendNotificationEnvelope $envelope): void
    {
        $this->deliver($envelope->recipient, $envelope->message, $envelope->channels, $envelope->priority, rethrow: false);
    }

    /**
     * @return NotificationLog[]
     */
    public function getHistoryForPatient(int $patientId): array
    {
        return $this->logRepository->findByPatient($patientId);
    }

    /**
     * @return NotificationLog[]
     */
    public function getHistoryForTreatment(int $treatmentId): array
    {
        return $this->logRepository->findByTreatment($treatmentId);
    }

    /**
     * @param string[] $channelTypes
     */
    private function deliver(
        Recipient $recipient,
        NotificationMessage $message,
        array $channelTypes,
        Priority $priority,
        bool $rethrow,
    ): void {
        try {
            foreach ($channelTypes as $channelType) {
                $channel = $this->channels->get($channelType);

                if (null === $channel) {
                    $this->log($recipient, $channelType, $priority, $message->template, 'failed', \sprintf('Channel "%s" is not registered.', $channelType), flush: false);
                    if ($rethrow) {
                        throw new ChannelNotFoundException($channelType);
                    }
                    continue;
                }

                if (!$channel->isAvailable()) {
                    // Пропускаем resolve()/send() заранее — не ждём таймаута от заведомо недоступного канала.
                    $this->log($recipient, $channelType, $priority, $message->template, 'failed', \sprintf('Channel "%s" is not available.', $channelType), flush: false);
                    if ($rethrow) {
                        throw new NotificationDeliveryException($channelType, 'Channel is not available.');
                    }
                    continue;
                }

                try {
                    $resolvedMessage = $this->templateResolver->resolve($message, $channelType);
                } catch (\Throwable $exception) {
                    $this->log($recipient, $channelType, $priority, $message->template, 'failed', $exception->getMessage(), flush: false);
                    if ($rethrow) {
                        throw new NotificationDeliveryException($channelType, $exception->getMessage());
                    }
                    continue;
                }

                $result = $this->sendWithRetries($channel, $recipient, $resolvedMessage);

                $this->log(
                    $recipient,
                    $channelType,
                    $priority,
                    $message->template,
                    $result->success ? $result->status : 'failed',
                    $result->errorMessage,
                    $result->externalId,
                    flush: false,
                );

                if (!$result->success && $rethrow) {
                    throw new NotificationDeliveryException($channelType, $result->errorMessage ?? 'Unknown error');
                }
            }
        } finally {
            // Один flush на все каналы вместо flush на каждую запись — срабатывает и при throw выше.
            $this->logRepository->flush();
        }
    }

    /**
     * Выполняет отправку через канал с повторными попытками для временных ошибок.
     * Задержки между повторами: 1s, 5s, 25s.
     */
    private function sendWithRetries(
        ChannelInterface $channel,
        Recipient $recipient,
        NotificationMessage $message,
    ): SendResult {
        $result = $this->attemptSend($channel, $recipient, $message);

        for (
            $retryNumber = 0; 
            $retryNumber < self::MAX_RETRY_ATTEMPTS && !$result->success && $result->retryable;
            $retryNumber++
        ) {
            $this->retrySleeper->wait($retryNumber);
            $result = $this->attemptSend($channel, $recipient, $message);
        }

        return $result;
    }

    private function attemptSend(
        ChannelInterface $channel,
        Recipient $recipient,
        NotificationMessage $message,
    ): SendResult {
        try {
            return $channel->send($recipient, $message);
        } catch (\Throwable $exception) {
            return SendResult::failure($exception->getMessage());
        }
    }

    private function log(
        Recipient $recipient,
        ?string $channelType,
        Priority $priority,
        ?string $templateCode,
        string $status,
        ?string $errorMessage,
        ?string $externalId = null,
        bool $flush = true,
    ): void {
        $log = (new NotificationLog())
            ->setPatientId($recipient->patientId)
            ->setTreatmentId($recipient->treatmentId)
            ->setChannelType($channelType)
            ->setRecipientAddress(null !== $channelType ? $recipient->addressFor($channelType) : null)
            ->setPriority($priority->value)
            ->setTemplateCode($templateCode)
            ->setStatus($status)
            ->setExternalId($externalId)
            ->setErrorMessage($errorMessage)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->logRepository->save($log, $flush);
    }
}
