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
    ) {
    }

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, $errorMessage);
    }
}
