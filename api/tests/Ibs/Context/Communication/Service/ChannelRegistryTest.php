<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Service;

use Ibs\Context\Communication\Service\ChannelRegistry;
use PHPUnit\Framework\TestCase;

class ChannelRegistryTest extends TestCase
{
    public function testIndexesChannelsByChannelType(): void
    {
        $sms = FakeChannel::succeeding('sms');
        $email = FakeChannel::succeeding('email');

        $registry = new ChannelRegistry([$sms, $email]);

        self::assertSame($sms, $registry->get('sms'));
        self::assertSame($email, $registry->get('email'));
        self::assertTrue($registry->has('sms'));
    }

    public function testReturnsNullForUnknownChannel(): void
    {
        $registry = new ChannelRegistry([]);

        self::assertNull($registry->get('push'));
        self::assertFalse($registry->has('push'));
    }
}
