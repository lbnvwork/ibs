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
    /** @var array<int, array{recipient: Recipient, message: NotificationMessage}> */
    public array $calls = [];

    private ?\Throwable $throws = null;
    private SendResult $result;

    public function __construct(
        private readonly string $channelType,
        ?SendResult $result = null,
        private bool $available = true,
    ) {
        $this->result = $result ?? SendResult::success();
    }

    public static function succeeding(string $channelType): self
    {
        return new self($channelType, SendResult::success());
    }

    public static function failing(string $channelType, string $errorMessage): self
    {
        return new self($channelType, SendResult::failure($errorMessage));
    }

    public static function unavailable(string $channelType): self
    {
        return new self($channelType, available: false);
    }

    public function throwing(\Throwable $exception): self
    {
        $this->throws = $exception;
        return $this;
    }

    public function send(Recipient $recipient, NotificationMessage $message): SendResult
    {
        $this->calls[] = ['recipient' => $recipient, 'message' => $message];

        if (null !== $this->throws) {
            throw $this->throws;
        }

        return $this->result;
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
