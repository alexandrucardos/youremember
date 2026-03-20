<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\OrderStatusInvalidException;
use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class OrderStatusInvalidExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseException(): void
    {
        $exception = new OrderStatusInvalidException('Test message');

        $this->assertInstanceOf(ProfileBaseException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Order status is invalid';
        $exception = new OrderStatusInvalidException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
