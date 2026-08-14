<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Model;

/**
 * Результат попытки отправки уведомления через конкретный канал.
 */
final class SendResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $errorMessage = null,
        public readonly ?string $externalId = null,
        public readonly bool $retryable = false,
        public readonly string $status = 'failed',
    ) {
    }

    public static function success(?string $externalId = null): self
    {
        return new self(true, externalId: $externalId, status: 'sent');
    }

    public static function delivered(?string $externalId = null): self
    {
        return new self(true, externalId: $externalId, status: 'delivered');
    }

    public static function failure(string $errorMessage, bool $retryable = false): self
    {
        return new self(false, $errorMessage, retryable: $retryable, status: 'failed');
    }
}