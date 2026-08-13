<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Model;

use Ibs\Context\Communication\Model\Recipient;
use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{
    public function testAddressForReturnsMatchingContactField(): void
    {
        $recipient = new Recipient(
            patientId: 1,
            phone: '+70000000000',
            email: 'patient@example.test',
            pushToken: 'push-token',
            maxUserId: 'max-42',
        );

        self::assertSame('+70000000000', $recipient->addressFor('sms'));
        self::assertSame('patient@example.test', $recipient->addressFor('email'));
        self::assertSame('push-token', $recipient->addressFor('push'));
        self::assertSame('max-42', $recipient->addressFor('max'));
    }

    public function testAddressForReturnsNullForUnknownOrUnsetChannel(): void
    {
        $recipient = new Recipient(patientId: 1);

        self::assertNull($recipient->addressFor('sms'));
        self::assertNull($recipient->addressFor('unknown'));
    }
}
