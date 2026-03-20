<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\ProfileStatusNotSetException;
use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class ProfileStatusNotSetExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseException(): void
    {
        $exception = new ProfileStatusNotSetException('Test message');

        $this->assertInstanceOf(ProfileBaseException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Profile status not set';
        $exception = new ProfileStatusNotSetException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
