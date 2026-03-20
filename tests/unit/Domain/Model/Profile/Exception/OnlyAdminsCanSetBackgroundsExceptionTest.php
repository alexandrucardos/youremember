<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\OnlyAdminsCanSetBackgroundsException;
use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class OnlyAdminsCanSetBackgroundsExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseException(): void
    {
        $exception = new OnlyAdminsCanSetBackgroundsException('Test message');

        $this->assertInstanceOf(ProfileBaseException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Only admins can set backgrounds';
        $exception = new OnlyAdminsCanSetBackgroundsException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
