<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Entity;

use Ibs\Context\TreatmentTherapy\Entity\Appointment;
use PHPUnit\Framework\TestCase;

class AppointmentTest extends TestCase
{
    public function testDoze2IsFloat(): void
    {
        $appointment = new Appointment();
        $appointment->setDoze2(2.75);

        $this->assertSame(2.75, $appointment->getDoze2());
    }
}
