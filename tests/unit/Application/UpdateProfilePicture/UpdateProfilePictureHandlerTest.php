<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\UpdateProfilePicture;

use App\Application\UpdateProfilePicture\UpdateProfilePictureCommand;
use App\Application\UpdateProfilePicture\UpdateProfilePictureHandler;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UpdateProfilePictureHandlerTest extends TestCase
{
    private const PROFILE_ID = 1;
    private const ORDER_ID = 123;
    private const USER_EMAIL = 'user@example.com';

    private ProfileRepositoryInterface&MockObject $eventRepository;
    private UpdateProfilePictureHandler $handler;

    public function testInvokeThrowsProfileNotFoundExceptionWhenProfileUuidIsNull(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn(null);
        $this->eventRepository->expects($this->never())->method('updateProfilePictureFile');

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage('Profile not found');

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeThrowsProfileNotFoundExceptionWhenProfileUuidIsEmpty(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn('');
        $this->eventRepository->expects($this->never())->method('updateProfilePictureFile');

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage('Profile not found');

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeUpdatesProfilePictureFileWhenProfileExists(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn((string) self::PROFILE_ID);

        $this->eventRepository
            ->expects($this->once())
            ->method('updateProfilePictureFile')
            ->with($this->callback(
                static fn(ProfileEntity $entity): bool => $entity->profileIdValueObject->value === self::PROFILE_ID
            ));

        ( $this->handler )($this->buildCommand());
    }

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->handler = new UpdateProfilePictureHandler($this->eventRepository);
    }

    private function buildCommand(): UpdateProfilePictureCommand
    {
        $uploadedFile = $this->createMock(UploadedFile::class);

        return new UpdateProfilePictureCommand(
            orderIdValueObject: new OrderIdValueObject(self::ORDER_ID),
            userEmail: new EmailValueObject(self::USER_EMAIL),
            profilePictureFile: $uploadedFile
        );
    }
}
