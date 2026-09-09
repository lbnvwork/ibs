<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\TreatmentTherapy;

use PHPUnit\Framework\TestCase;

class Mkb10FixtureTest extends TestCase
{
    public function testDocxMissingCodesPresent(): void
    {
        $sql = file_get_contents(dirname(__DIR__, 4) . '/fixtures/reference/mkb10.sql');

        self::assertIsString($sql);
        self::assertStringContainsString("'I82.4'", $sql);
        self::assertStringContainsString("'T82.86'", $sql);
    }
}
