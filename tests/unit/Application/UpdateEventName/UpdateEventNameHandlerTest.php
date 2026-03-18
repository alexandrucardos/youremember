<?php

declare(strict_types=1);

namespace App\Tests\unit\Application\UpdateEventName;

use App\Application\UpdateEventName\UpdateProfileNameCommand;
use App\Application\UpdateEventName\UpdateProfileNameHandler;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\Message\ProfileNotValidException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\ProfileNameValueObject;
use App\ValueObject\Status;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;

class UpdateEventNameHandlerTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    public static function validEventDataProvider(): array
    {
        return [
            'event with custom name and font' => [
                'event_name' => 'My Wedding',
                'event_font' => 'elegant'
            ],
            'event with another name and font' => [
                'event_name' => 'Birthday Party',
                'event_font' => 'modern'
            ]
        ];
    }

    /**
     * @dataProvider validEventDataProvider
     */
    public function testInvokeUpdatesEventNameAndFont(string $eventName, string $eventFont): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('getExistingProfileUuid')
            ->with($this->callback(
                static fn(UuidValueObject $uuidValueObject): bool => $uuidValueObject->value === self::UUID
            ))
            ->willReturn([self::UUID, Status::VALID]);

        $eventRepository
            ->expects($this->once())
            ->method('updateProfileNameAndFont')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity): bool => (
                    $eventEntity->profileUuidValueObject->value === self::UUID
                    && $eventEntity->getProfileName()->value === $eventName
                    && $eventEntity->getProfileNameFont()->value === $eventFont
                )
            ));

        $command = new UpdateProfileNameCommand(
            new UuidValueObject(self::UUID),
            new ProfileNameValueObject($eventName),
            new ProfileNameFontValueObject($eventFont)
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }

    public function testInvokeThrowsEventNotValidExceptionWhenStatusIsInvalid(): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('getExistingProfileUuid')
            ->willReturn([self::UUID, Status::INVALID]);

        $eventRepository->expects($this->never())->method('updateProfileNameAndFont');

        $this->expectException(ProfileNotValidException::class);

        $command = new UpdateProfileNameCommand(
            new UuidValueObject(self::UUID),
            new ProfileNameValueObject('My Wedding'),
            new ProfileNameFontValueObject('elegant')
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenRepositoryThrowsIt(): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('getExistingProfileUuid')
            ->willThrowException(new ProfileNotFoundException('Event not found'));

        $eventRepository->expects($this->never())->method('updateProfileNameAndFont');

        $this->expectException(ProfileNotFoundException::class);

        $command = new UpdateProfileNameCommand(
            new UuidValueObject(self::UUID),
            new ProfileNameValueObject('My Wedding'),
            new ProfileNameFontValueObject('elegant')
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }
}
