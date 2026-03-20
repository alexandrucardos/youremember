<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Message;

use App\Domain\Model\Profile\Message\MaximumProfileItemsReachedException;
use App\Domain\Model\Profile\Message\ProfileBaseMsgException;
use PHPUnit\Framework\TestCase;

class MaximumProfileItemsReachedExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseMsgException(): void
    {
        $exception = new MaximumProfileItemsReachedException('Test message');

        $this->assertInstanceOf(ProfileBaseMsgException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Maximum profile items reached';
        $exception = new MaximumProfileItemsReachedException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCodeIsMaximumMediaItemsReached(): void
    {
        $exception = new MaximumProfileItemsReachedException('Test message');

        $this->assertSame(4003, $exception->getCode());
    }
}
