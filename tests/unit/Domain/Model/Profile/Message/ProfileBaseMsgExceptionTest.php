<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Message;

use App\Domain\Model\Profile\Message\ProfileBaseMsgException;
use PHPUnit\Framework\TestCase;

class ProfileBaseMsgExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new ProfileBaseMsgException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Test exception message';
        $exception = new ProfileBaseMsgException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCodeIsStored(): void
    {
        $code = 123;
        $exception = new ProfileBaseMsgException('Test message', $code);

        $this->assertSame($code, $exception->getCode());
    }
}
