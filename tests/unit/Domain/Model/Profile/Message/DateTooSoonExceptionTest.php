<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Message;

use App\Domain\Model\Profile\Message\DateTooSoonException;
use App\Domain\Model\Profile\Message\ProfileBaseMsgException;
use PHPUnit\Framework\TestCase;

class DateTooSoonExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseMsgException(): void
    {
        $exception = new DateTooSoonException('Test message');

        $this->assertInstanceOf(ProfileBaseMsgException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Date is too soon';
        $exception = new DateTooSoonException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCodeIsDateTooSoon(): void
    {
        $exception = new DateTooSoonException('Test message');

        $this->assertSame(4002, $exception->getCode());
    }
}
