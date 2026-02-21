<?php

namespace App\Tests\unit\Application\AddImageArchiveEmail;

use App\Application\AddImageArchiveEmail\AddImageArchiveEmailCommand;
use App\Application\AddImageArchiveEmail\AddImageArchiveEmailHandler;
use App\Domain\Model\ImageArchive\EventNotFoundException;
use App\Domain\Model\ImageArchive\ImageArchiveEntity;
use App\Domain\Model\ImageArchive\ImageArchiveRepositoryInterface;
use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;

class AddImageArchiveEmailHandlerTest extends TestCase
{
    public static function validCommandDataProvider(): array
    {
        return [
            'standard email and uuid' => [
                'email' => 'user@example.com',
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            ]
        ];
    }

    /**
     * @dataProvider validCommandDataProvider
     */
    public function testInvokeSavesImageArchiveEntity(string $email, string $uuid): void
    {
        $imageArchiveRepository = $this->createMock(ImageArchiveRepositoryInterface::class);
        $imageArchiveRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (ImageArchiveEntity $imageArchiveEntity) use ($email, $uuid) {
                return $imageArchiveEntity->emailValueObject->value === $email
                    && $imageArchiveEntity->eventUuidValueObject->value === $uuid;
            }));

        $command = new AddImageArchiveEmailCommand(
            emailValueObject: new EmailValueObject($email),
            uuidValueObject: new UuidValueObject($uuid),
        );

        $handler = new AddImageArchiveEmailHandler($imageArchiveRepository);
        $handler($command);
    }

    public function testInvokePropagatesEventNotFoundException(): void
    {
        $imageArchiveRepository = $this->createMock(ImageArchiveRepositoryInterface::class);
        $imageArchiveRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new EventNotFoundException());

        $command = new AddImageArchiveEmailCommand(
            emailValueObject: new EmailValueObject('user@example.com'),
            uuidValueObject: new UuidValueObject('550e8400-e29b-41d4-a716-446655440000'),
        );

        $this->expectException(EventNotFoundException::class);

        $handler = new AddImageArchiveEmailHandler($imageArchiveRepository);
        $handler($command);
    }
}