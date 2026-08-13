<?php

declare(strict_types=1);

namespace App\Tests\Ibs\Context\Communication\Model;

use Ibs\Context\Communication\Model\SendResult;
use PHPUnit\Framework\TestCase;

class SendResultTest extends TestCase
{
    public function testSuccessFactoryHasNoErrorMessage(): void
    {
        $result = SendResult::success();

        self::assertTrue($result->success);
        self::assertNull($result->errorMessage);
    }

    public function testFailureFactoryCarriesErrorMessage(): void
    {
        $result = SendResult::failure('Provider timeout');

        self::assertFalse($result->success);
        self::assertSame('Provider timeout', $result->errorMessage);
    }
}
