<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\UpdateEventName;

use App\Application\UpdateProfileName\UpdateProfileNameCommand;
use App\Application\UpdateProfileName\UpdateProfileNameHandler;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;
use App\Domain\ValueObject\ProfileNameValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateEventNameHandlerTest extends TestCase
{
    private const PROFILE_ID = 1;
    private const ORDER_ID = 123;
    private const USER_EMAIL = 'user@example.com';

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
            ->method('getExistingProfileIdForOrderIdAndEmail')
            ->willReturn((string) self::PROFILE_ID);

        $eventRepository
            ->expects($this->once())
            ->method('updateProfileNameAndFont')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity): bool => (
                    $eventEntity->profileIdValueObject->value === self::PROFILE_ID
                    && $eventEntity->getProfileName()->value === $eventName
                    && $eventEntity->getProfileNameFont()->value === $eventFont
                )
            ));

        $command = new UpdateProfileNameCommand(
            new OrderIdValueObject(self::ORDER_ID),
            new EmailValueObject(self::USER_EMAIL),
            new ProfileNameValueObject($eventName, $this->createStub(TranslatorInterface::class)),
            new ProfileNameFontValueObject($eventFont)
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenUuidIsNull(): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository->expects($this->once())->method('getExistingProfileIdForOrderIdAndEmail')->willReturn(null);

        $eventRepository->expects($this->never())->method('updateProfileNameAndFont');

        $this->expectException(ProfileNotFoundException::class);

        $command = new UpdateProfileNameCommand(
            new OrderIdValueObject(self::ORDER_ID),
            new EmailValueObject(self::USER_EMAIL),
            new ProfileNameValueObject('My Wedding', $this->createStub(TranslatorInterface::class)),
            new ProfileNameFontValueObject('elegant')
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenUuidIsEmpty(): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository->expects($this->once())->method('getExistingProfileIdForOrderIdAndEmail')->willReturn('');

        $eventRepository->expects($this->never())->method('updateProfileNameAndFont');

        $this->expectException(ProfileNotFoundException::class);

        $command = new UpdateProfileNameCommand(
            new OrderIdValueObject(self::ORDER_ID),
            new EmailValueObject(self::USER_EMAIL),
            new ProfileNameValueObject('My Wedding', $this->createStub(TranslatorInterface::class)),
            new ProfileNameFontValueObject('elegant')
        );

        $handler = new UpdateProfileNameHandler($eventRepository);
        $handler($command);
    }
}
