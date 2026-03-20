<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\ListProfileInformationQueryNullException;
use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class ListProfileInformationQueryNullExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseException(): void
    {
        $exception = new ListProfileInformationQueryNullException('Test message');

        $this->assertInstanceOf(ProfileBaseException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Query is null';
        $exception = new ListProfileInformationQueryNullException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
