<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Entity;

use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestHistoryTest extends TestCase
{
    public function testDoze2IsFloat(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setDoze2(2.75);

        $this->assertSame(2.75, $testHistory->getDoze2());
    }

    public function testDoze2ZeroBecomesMinusOne(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setDoze2(0.0);

        // Прямой вызов PrePersist-колбэка: нормализация 0.0 → -1.0.
        $testHistory->setCreatedAtValue();

        $this->assertSame(-1.0, $testHistory->getDoze2());
    }

    public function testDoze2RejectsNonMultipleOf025(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setMno(2.5);
        $testHistory->setDoze(2.5);
        $testHistory->setDoze2(2.4);

        $this->assertContains('doze2', $this->violationPaths($testHistory));
    }

    public function testDoze2RejectsDeviationOtherThan025Or05(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setMno(2.5);
        $testHistory->setDoze(2.5);
        $testHistory->setDoze2(3.25);

        $this->assertContains('doze2', $this->violationPaths($testHistory));
    }

    public function testDoze2AcceptsValidAlternation(): void
    {
        $testHistory = new TestHistory();
        $testHistory->setMno(2.5);
        $testHistory->setDoze(2.5);
        $testHistory->setDoze2(2.75);

        $this->assertCount(0, $this->violationPaths($testHistory));
    }

    private function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /** @return string[] */
    private function violationPaths(TestHistory $testHistory): array
    {
        $paths = [];
        foreach ($this->createValidator()->validate($testHistory) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }
}
