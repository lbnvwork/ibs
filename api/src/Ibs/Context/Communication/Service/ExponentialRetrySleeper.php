<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service;

/**
 * Экспоненциальные задержки для MAX-канала: 1s, 5s, 25s.
 */
final class ExponentialRetrySleeper implements RetrySleeperInterface
{
    /** @var array<int, int> задержки для каждого номера retry (0, 1, 2) */
    private const DELAY_SECONDS = [1, 5, 25];

    private readonly mixed $sleepFunction;

    public function __construct(?callable $sleepFunction = null)
    {
        $this->sleepFunction = $sleepFunction;
    }

    public function wait(int $retryNumber): void
    {
        if (!isset(self::DELAY_SECONDS[$retryNumber])) {
            throw new \InvalidArgumentException(
                \sprintf('Unsupported retry number "%d".', $retryNumber),
            );
        }

        $microseconds = self::DELAY_SECONDS[$retryNumber] * 1_000_000;
        $sleep = $this->sleepFunction ?? static function (int $usec): void {
            \usleep($usec);
        };
        $sleep($microseconds);
    }
}