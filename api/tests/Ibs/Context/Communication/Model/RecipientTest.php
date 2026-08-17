<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Model;

use Ibs\Context\Communication\Model\Recipient;
use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{
    public function testExposesPatientAndTreatmentIds(): void
    {
        $recipient = new Recipient(patientId: 1, treatmentId: 42);

        self::assertSame(1, $recipient->patientId);
        self::assertSame(42, $recipient->treatmentId);
    }

    public function testPatientAndTreatmentIdsCanBeNull(): void
    {
        $recipient = new Recipient();

        self::assertNull($recipient->patientId);
        self::assertNull($recipient->treatmentId);
    }

    public function testExposesOnlyPatientId(): void
    {
        $recipient = new Recipient(patientId: 7);

        self::assertSame(7, $recipient->patientId);
        self::assertNull($recipient->treatmentId);
    }

    public function testExposesOnlyTreatmentId(): void
    {
        $recipient = new Recipient(treatmentId: 9);

        self::assertNull($recipient->patientId);
        self::assertSame(9, $recipient->treatmentId);
    }

    public function testPropertiesAreReadonly(): void
    {
        $reflection = new \ReflectionClass(Recipient::class);

        self::assertTrue($reflection->getProperty('patientId')->isReadOnly());
        self::assertTrue($reflection->getProperty('treatmentId')->isReadOnly());
    }
}
