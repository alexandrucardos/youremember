<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\DeleteMedia;

use App\Application\DeleteMedia\DeleteMediaCommand;
use App\Application\DeleteMedia\DeleteMediaHandler;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\Exception\GuestUsersCannotDeleteMultipleImagesException;
use App\Domain\Model\Profile\Exception\MediaFileDeletionNotAllowedException;
use App\Domain\Model\Profile\Message\ProfileNotValidException;
use App\ValueObject\HashValueObject;
use App\ValueObject\Status;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteMediaHandlerTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';
    private const USER_IDENTIFIER = 'userId';

    private ProfileRepositoryInterface&MockObject $eventRepository;
    private DeleteMediaHandler $handler;

    public static function adminFilePathsDataProvider(): array
    {
        return [
            'single file' => [['12345/client/image.jpg']],
            'multiple files' => [['12345/client/image.jpg', '12345/client/video.mp4']]
        ];
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenEventUuidIsNull(): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([null, null]);
        $this->eventRepository->expects($this->never())->method('deleteMediaFiles');

        $this->expectException(ProfileNotFoundException::class);

        ( $this->handler )($this->buildAdminCommand());
    }

    public function testInvokeThrowsEventNotValidExceptionWhenStatusIsInvalid(): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::INVALID]);
        $this->eventRepository->expects($this->never())->method('deleteMediaFiles');

        $this->expectException(ProfileNotValidException::class);

        ( $this->handler )($this->buildAdminCommand());
    }

    public function testInvokeThrowsMediaFileDeletionNotAllowedWhenGuestDeletesOtherUserFiles(): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::VALID]);

        $this->eventRepository->expects($this->never())->method('deleteMediaFiles');

        $this->expectException(MediaFileDeletionNotAllowedException::class);

        ( $this->handler )(new DeleteMediaCommand(
            eventUuidValueObject: new UuidValueObject(self::UUID),
            userIdentifier: new HashValueObject(self::USER_IDENTIFIER),
            userRole: UserRole::ROLE_GUEST,
            filePaths: ['other_user_hash/file.jpg']
        ));
    }

    /**
     * @dataProvider adminFilePathsDataProvider
     */
    public function testInvokeCallsDeleteMediaFilesForAdminUser(array $filePaths): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::VALID]);

        $this->eventRepository
            ->expects($this->once())
            ->method('deleteMediaFiles')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity): bool => (
                    $eventEntity->eventUuidValueObject->value === self::UUID
                    && $eventEntity->getIsAdmin() === true
                )
            ));

        ( $this->handler )($this->buildAdminCommand($filePaths));
    }

    public function testInvokeThrowsGuestUsersCannotDeleteMultipleImagesException(): void
    {
        $paths = ['123/userId/file.jpg', '123/userId/file2.jpg'];

        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::VALID]);

        $this->eventRepository->expects($this->never())->method('deleteMediaFiles');

        $this->expectException(GuestUsersCannotDeleteMultipleImagesException::class);

        ( $this->handler )(new DeleteMediaCommand(
            eventUuidValueObject: new UuidValueObject(self::UUID),
            userIdentifier: new HashValueObject(self::USER_IDENTIFIER),
            userRole: UserRole::ROLE_GUEST,
            filePaths: $paths
        ));
    }

    public function testInvokeCallsDeleteMediaFilesForGuestDeletingOwnFiles(): void
    {
        $paths = ['123/userId/file.jpg'];

        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::VALID]);

        $this->eventRepository
            ->expects($this->once())
            ->method('deleteMediaFiles')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity): bool => (
                    $eventEntity->eventUuidValueObject->value === self::UUID
                    && $eventEntity->getIsAdmin() === false
                )
            ));

        ( $this->handler )(new DeleteMediaCommand(
            eventUuidValueObject: new UuidValueObject(self::UUID),
            userIdentifier: new HashValueObject(self::USER_IDENTIFIER),
            userRole: UserRole::ROLE_GUEST,
            filePaths: $paths
        ));
    }

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->handler = new DeleteMediaHandler($this->eventRepository);
    }

    private function buildAdminCommand(array $filePaths = ['some/path/file.jpg']): DeleteMediaCommand
    {
        return new DeleteMediaCommand(
            eventUuidValueObject: new UuidValueObject(self::UUID),
            userIdentifier: new HashValueObject(self::USER_IDENTIFIER),
            userRole: UserRole::ROLE_ADMIN,
            filePaths: $filePaths
        );
    }
}
