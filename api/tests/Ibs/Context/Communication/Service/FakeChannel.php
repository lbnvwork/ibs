<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Model\NotificationMessage;
use Ibs\Context\Communication\Model\Recipient;
use Ibs\Context\Communication\Model\SendResult;
use Ibs\Context\Communication\Service\ChannelInterface;

/**
 * Тестовый двойник канала связи: не обращается к реальным транспортам,
 * запоминает вызовы send() и позволяет задать результат/исключение заранее.
 */
final class FakeChannel implements ChannelInterface
{
    /** @var array<int, array{recipient: Recipient, address: string, message: NotificationMessage}> */
    public array $calls = [];

    /** @var SendResult[] */
    private array $results = [];

    private int $resultIndex = 0;

    private ?\Throwable $throws = null;

    public function __construct(
        private readonly string $channelType,
        ?SendResult $result = null,
        private bool $available = true,
    ) {
        if (null !== $result) {
            $this->results[] = $result;
        }
    }

    public static function succeeding(string $channelType): self
    {
        return new self($channelType, SendResult::success());
    }

    public static function succeedingWithExternalId(string $channelType, string $externalId): self
    {
        return new self($channelType, SendResult::success(externalId: $externalId));
    }

    public static function failing(string $channelType, string $errorMessage): self
    {
        return new self($channelType, SendResult::failure($errorMessage));
    }

    public static function failingRetryable(string $channelType, string $errorMessage): self
    {
        return new self($channelType, SendResult::failure($errorMessage, retryable: true));
    }

    public static function unavailable(string $channelType): self
    {
        return new self($channelType, available: false);
    }

    public function withResults(SendResult ...$results): self
    {
        $this->results = $results;
        $this->resultIndex = 0;

        return $this;
    }

    public function throwing(\Throwable $exception): self
    {
        $this->throws = $exception;
        return $this;
    }

    public function send(Recipient $recipient, string $address, NotificationMessage $message): SendResult
    {
        $this->calls[] = ['recipient' => $recipient, 'address' => $address, 'message' => $message];

        if (null !== $this->throws) {
            throw $this->throws;
        }

        if ([] === $this->results) {
            return SendResult::success();
        }

        $index = $this->resultIndex;
        $this->resultIndex++;

        return $this->results[min($index, \count($this->results) - 1)];
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getChannelType(): string
    {
        return $this->channelType;
    }
}
