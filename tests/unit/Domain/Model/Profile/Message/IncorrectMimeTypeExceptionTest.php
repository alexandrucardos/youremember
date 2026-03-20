<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Message;

use App\Domain\Model\Profile\Message\IncorrectMimeTypeException;
use App\Domain\Model\Profile\Message\ProfileBaseMsgException;
use PHPUnit\Framework\TestCase;

class IncorrectMimeTypeExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseMsgException(): void
    {
        $exception = new IncorrectMimeTypeException('Test message');

        $this->assertInstanceOf(ProfileBaseMsgException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Incorrect mime type';
        $exception = new IncorrectMimeTypeException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionCodeIsIncorrectMimeType(): void
    {
        $exception = new IncorrectMimeTypeException('Test message');

        $this->assertSame(4001, $exception->getCode());
    }
}
