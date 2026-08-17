<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Service\RetrySleeperInterface;

/**
 * Быстрый sleeper для тестов retry-логики: не ожидает реальное время.
 */
final class ImmediateRetrySleeper implements RetrySleeperInterface
{
    /** @var int[] */
    public array $waitedRetryNumbers = [];

    public function wait(int $retryNumber): void
    {
        $this->waitedRetryNumbers[] = $retryNumber;
    }
}