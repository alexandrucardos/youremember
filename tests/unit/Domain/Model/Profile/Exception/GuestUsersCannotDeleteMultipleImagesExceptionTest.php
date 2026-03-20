<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\GuestUsersCannotDeleteMultipleImagesException;
use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class GuestUsersCannotDeleteMultipleImagesExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseException(): void
    {
        $exception = new GuestUsersCannotDeleteMultipleImagesException('Test message');

        $this->assertInstanceOf(ProfileBaseException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Guest users cannot delete multiple images';
        $exception = new GuestUsersCannotDeleteMultipleImagesException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
