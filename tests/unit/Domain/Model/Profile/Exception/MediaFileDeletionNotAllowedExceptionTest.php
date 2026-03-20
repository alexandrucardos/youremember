<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\MediaFileDeletionNotAllowedException;
use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class MediaFileDeletionNotAllowedExceptionTest extends TestCase
{
    public function testExceptionExtendsProfileBaseException(): void
    {
        $exception = new MediaFileDeletionNotAllowedException('Test message');

        $this->assertInstanceOf(ProfileBaseException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Media file deletion not allowed';
        $exception = new MediaFileDeletionNotAllowedException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
