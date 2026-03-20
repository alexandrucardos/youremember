<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Message;

use App\Domain\Model\Profile\Message\ProfileNotValidException;
use App\Domain\Model\Profile\Message\ProfileBaseMsgException;
use PHPUnit\Framework\TestCase;

class ProfileNotValidExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseMsgException(): void
    {
        $exception = new ProfileNotValidException('Test message');

        $this->assertInstanceOf(ProfileBaseMsgException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Profile not valid';
        $exception = new ProfileNotValidException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCodeIsEventNotValid(): void
    {
        $exception = new ProfileNotValidException('Test message');

        $this->assertSame(4000, $exception->getCode());
    }
}
