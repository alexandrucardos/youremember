<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\UserIdentityNotMatchingException;
use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class UserIdentityNotMatchingExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseException(): void
    {
        $exception = new UserIdentityNotMatchingException('Test message');

        $this->assertInstanceOf(ProfileBaseException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'User identity not matching';
        $exception = new UserIdentityNotMatchingException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
