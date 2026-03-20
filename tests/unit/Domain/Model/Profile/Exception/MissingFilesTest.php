<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Profile\Exception;

use App\Domain\Model\Profile\Exception\MissingFiles;
use App\Domain\Model\Profile\Exception\ProfileBaseException;
use PHPUnit\Framework\TestCase;

class MissingFilesTest extends TestCase
{
    public function testExceptionExtendsProfileBaseException(): void
    {
        $exception = new MissingFiles('Test message');

        $this->assertInstanceOf(ProfileBaseException::class, $exception);
    }

    public function testExceptionMessageIsStored(): void
    {
        $message = 'Missing files';
        $exception = new MissingFiles($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
