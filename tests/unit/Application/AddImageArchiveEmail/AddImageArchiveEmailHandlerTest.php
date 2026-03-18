<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\AddImageArchiveEmail;

use App\Application\SubscribeForArchiveArchive\SubscribeForArchiveArchiveHandler;
use App\Application\SubscribeForArchiveArchive\SubscribeForArchiveCommand;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\ValueObject\EmailValueObject;
use App\ValueObject\Status;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;

class AddImageArchiveEmailHandlerTest extends TestCase
{
    public static function validCommandDataProvider(): array
    {
        return [
            'standard email and uuid' => [
                'email' => 'user@example.com',
                'uuid' => '550e8400-e29b-41d4-a716-446655440000'
            ]
        ];
    }

    /**
     * @dataProvider validCommandDataProvider
     */
    public function testInvokeSavesImageArchiveEntity(string $email, string $uuid): void
    {
        $eventRepositoryInterface = $this->createMock(ProfileRepositoryInterface::class);

        $eventRepositoryInterface
            ->expects($this->once())
            ->method('getExistingProfileUuid')
            ->willReturn([$uuid, Status::VALID]);

        $eventRepositoryInterface
            ->expects($this->once())
            ->method('saveArchiveEmailForEvent')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity) => (
                    $eventEntity->getImageArchiveEmail()->value === $email
                    && $eventEntity->profileUuidValueObject->value === $uuid
                )
            ));

        $command = new SubscribeForArchiveCommand(
            emailValueObject: new EmailValueObject($email),
            uuidValueObject: new UuidValueObject($uuid)
        );

        $handler = new SubscribeForArchiveArchiveHandler($eventRepositoryInterface);
        $handler($command);
    }

    public function testInvokePropagatesEventNotFoundException(): void
    {
        $eventRepositoryInterface = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepositoryInterface
            ->expects($this->once())
            ->method('getExistingProfileUuid')
            ->willReturn(['550e8400-e29b-41d4-a716-446655440000', Status::VALID]);

        $eventRepositoryInterface
            ->expects($this->once())
            ->method('saveArchiveEmailForEvent')
            ->willThrowException(new ProfileNotFoundException());

        $command = new SubscribeForArchiveCommand(
            uuidValueObject: new UuidValueObject('550e8400-e29b-41d4-a716-446655440000'),
            emailValueObject: new EmailValueObject('user@example.com')
        );

        $this->expectException(ProfileNotFoundException::class);

        $handler = new SubscribeForArchiveArchiveHandler($eventRepositoryInterface);
        $handler($command);
    }
}
