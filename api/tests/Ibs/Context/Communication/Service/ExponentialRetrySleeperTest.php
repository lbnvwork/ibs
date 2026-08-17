<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Service\ExponentialRetrySleeper;
use PHPUnit\Framework\TestCase;

final class ExponentialRetrySleeperTest extends TestCase
{
    public function testWaitUsesExpectedMicrosecondDelays(): void
    {
        $sleeps = [];
        $sleeper = new ExponentialRetrySleeper(
            static function (int $microseconds) use (&$sleeps): void {
                $sleeps[] = $microseconds;
            },
        );

        $sleeper->wait(0);
        $sleeper->wait(1);
        $sleeper->wait(2);

        self::assertSame(
            [
                1_000_000,
                5_000_000,
                25_000_000,
            ],
            $sleeps,
        );
    }

    public function testUnsupportedRetryNumberThrowsInvalidArgumentException(): void
    {
        $sleeper = new ExponentialRetrySleeper(static fn (int $microseconds): int => 0);

        $this->expectException(\InvalidArgumentException::class);
        $sleeper->wait(3);
    }

    public function testNegativeRetryNumberThrowsInvalidArgumentException(): void
    {
        $sleeper = new ExponentialRetrySleeper(static fn (int $microseconds): int => 0);

        $this->expectException(\InvalidArgumentException::class);
        $sleeper->wait(-1);
    }
}