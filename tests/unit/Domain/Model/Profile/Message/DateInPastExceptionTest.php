<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Message;

use App\Domain\Model\Profile\Message\DateInPastException;
use App\Domain\Model\Profile\Message\ProfileBaseMsgException;
use PHPUnit\Framework\TestCase;

class DateInPastExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseMsgException(): void
    {
        $exception = new DateInPastException('Test message');

        $this->assertInstanceOf(ProfileBaseMsgException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Date is in future';
        $exception = new DateInPastException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCodeIsDateInFuture(): void
    {
        $exception = new DateInPastException('Test message');

        $this->assertSame(4004, $exception->getCode());
    }
}
