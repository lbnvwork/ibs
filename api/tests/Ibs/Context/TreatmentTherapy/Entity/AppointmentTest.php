<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Entity;

use Ibs\Context\TreatmentTherapy\Entity\Appointment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppointmentTest extends TestCase
{
    public function testDoze2IsFloat(): void
    {
        $appointment = new Appointment();
        $appointment->setDoze2(2.75);

        $this->assertSame(2.75, $appointment->getDoze2());
    }

    public function testNextTestDtBeforeAppointmentDtIsRejected(): void
    {
        $appointment = new Appointment();
        $appointment->setAppointmentDt(new \DateTime('2026-08-10 10:00:00'));
        $appointment->setNextTestDt(new \DateTime('2026-08-09 00:00:00'));

        $this->assertContains('nextTestDt', $this->violationPaths($appointment));
    }

    public function testNextTestDtAcceptsNullOrFutureDate(): void
    {
        $appointment = new Appointment();
        $appointment->setAppointmentDt(new \DateTime('2026-08-10 10:00:00'));
        $appointment->setNextTestDt(new \DateTime('2026-08-15 00:00:00'));

        $this->assertCount(0, $this->violationPaths($appointment));

        $appointment->setNextTestDt(null);
        $this->assertCount(0, $this->violationPaths($appointment));
    }

    private function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /** @return string[] */
    private function violationPaths(Appointment $appointment): array
    {
        $paths = [];
        foreach ($this->createValidator()->validate($appointment) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }
}
