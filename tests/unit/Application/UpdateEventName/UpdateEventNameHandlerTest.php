<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\UpdateEventName;

use App\Application\UpdateEventName\UpdateProfileNameCommand;
use App\Application\UpdateEventName\UpdateProfileNameHandler;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\Message\ProfileNotValidException;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
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
            ->method('getExistingEventUuidAndStatus')
            ->with($this->callback(
                static fn(UuidValueObject $uuidValueObject): bool => $uuidValueObject->value === self::UUID
            ))
            ->willReturn([self::UUID, Status::VALID]);

        $eventRepository
            ->expects($this->once())
            ->method('updateEventNameAndFont')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity): bool => (
                    $eventEntity->eventUuidValueObject->value === self::UUID
                    && $eventEntity->getEventName()->value === $eventName
                    && $eventEntity->getEventNameFont()->value === $eventFont
                )
            ));

        $command = new UpdateProfileNameCommand(
            new UuidValueObject(self::UUID),
            new EventNameValueObject($eventName),
            new EventNameFontValueObject($eventFont)
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }

    public function testInvokeThrowsEventNotValidExceptionWhenStatusIsInvalid(): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('getExistingEventUuidAndStatus')
            ->willReturn([self::UUID, Status::INVALID]);

        $eventRepository->expects($this->never())->method('updateEventNameAndFont');

        $this->expectException(ProfileNotValidException::class);

        $command = new UpdateProfileNameCommand(
            new UuidValueObject(self::UUID),
            new EventNameValueObject('My Wedding'),
            new EventNameFontValueObject('elegant')
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenRepositoryThrowsIt(): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('getExistingEventUuidAndStatus')
            ->willThrowException(new ProfileNotFoundException('Event not found'));

        $eventRepository->expects($this->never())->method('updateEventNameAndFont');

        $this->expectException(ProfileNotFoundException::class);

        $command = new UpdateProfileNameCommand(
            new UuidValueObject(self::UUID),
            new EventNameValueObject('My Wedding'),
            new EventNameFontValueObject('elegant')
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }
}
