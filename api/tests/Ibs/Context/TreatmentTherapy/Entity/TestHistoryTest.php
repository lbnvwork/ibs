<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy\Entity;

use Ibs\Context\TreatmentTherapy\Entity\TestHistory;
use PHPUnit\Framework\TestCase;

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
}
