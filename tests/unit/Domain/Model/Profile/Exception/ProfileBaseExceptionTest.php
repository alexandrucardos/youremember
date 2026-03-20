<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class ProfileBaseExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new ProfileBaseException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Test exception message';
        $exception = new ProfileBaseException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCodeIsStored(): void
    {
        $code = 123;
        $exception = new ProfileBaseException('Test message', $code);

        $this->assertSame($code, $exception->getCode());
    }
}
