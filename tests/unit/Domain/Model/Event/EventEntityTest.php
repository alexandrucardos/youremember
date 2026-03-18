<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Event;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\Exception\GuestUsersCannotDeleteMultipleImagesException;
use App\Domain\Model\Profile\Exception\MediaFileDeletionNotAllowedException;
use App\Domain\Model\Profile\Exception\MissingFiles;
use App\Domain\Model\Profile\Message\ProfileNotValidException;
use App\Domain\Model\User\UserEntity;
use App\Domain\Model\Profile\Message\IncorrectMimeTypeException;
use App\Domain\Model\Profile\Message\MaximumProfileItemsReachedException;
use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\ProfileNameValueObject;
use App\ValueObject\HashValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use App\ValueObject\Status;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EventEntityTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    public static function statusDataProvider(): array
    {
        return [
            'valid status' => ['status' => Status::VALID]
        ];
    }

    public static function maximumItemsDataProvider(): array
    {
        return [
            'at limit' => ['maxItems' => 10, 'existingItems' => 10, 'newFiles' => 1],
            'multiple files over' => ['maxItems' => 5, 'existingItems' => 3, 'newFiles' => 3]
        ];
    }

    public static function unsupportedMimeTypesDataProvider(): array
    {
        return [
            'pdf' => [['application/pdf']],
            'svg' => [['image/svg+xml']],
            'mixed valid and invalid' => [['image/jpeg', 'application/pdf']]
        ];
    }

    public static function acceptedMimeTypesDataProvider(): array
    {
        return [
            'jpeg' => [['image/jpeg']],
            'png' => [['image/png']],
            'gif' => [['image/gif']],
            'mp4' => [['video/mp4']],
            'webm' => [['video/webm']],
            'mixed valid' => [['image/jpeg', 'video/mp4']]
        ];
    }

    public static function adminFilePathsDataProvider(): array
    {
        return [
            'single file' => [['12345/client/image.jpg']],
            'multiple files' => [['12345/client/image.jpg', '12345/client/video.mp4']]
        ];
    }

    public function testConstructorStoresUuid(): void
    {
        $uuid = new UuidValueObject(self::UUID);

        $entity = new ProfileEntity(profileUuidValueObject: $uuid, eventStatus: Status::VALID);

        $this->assertSame($uuid, $entity->profileUuidValueObject);
    }

    public function testConstructorThrowsEventNotValidForInvalidStatus(): void
    {
        $this->expectException(ProfileNotValidException::class);

        new ProfileEntity(profileUuidValueObject: new UuidValueObject(self::UUID), eventStatus: Status::INVALID);
    }

    public function testSetAndGetFeedbackValueObject(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID), Status::VALID);
        $feedback = new FeedbackValueObject('Great event!');

        $entity->setFeedbackValueObject($feedback);

        $this->assertSame($feedback, $entity->getFeedbackValueObject());
    }

    public function testSetAndGetImageArchiveEmail(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID), Status::VALID);
        $email = new EmailValueObject('user@example.com');

        $entity->setImageArchiveEmail($email);

        $this->assertSame($email, $entity->getImageArchiveEmail());
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testConstructorStoresProperties(Status $status): void
    {
        $uuid = new UuidValueObject(self::UUID);

        $entity = new ProfileEntity(profileUuidValueObject: $uuid, eventStatus: $status);

        $this->assertSame($uuid, $entity->profileUuidValueObject);
    }

    public function testSetMediaUserIdentifierStoresValueAndReturnsSelf(): void
    {
        $entity = $this->buildEntity();

        $result = $entity->setIsAdmin(true)->setMediaUserIdentifier(new HashValueObject('client'));

        $this->assertSame($entity, $result);
        $this->assertSame('admin', $entity->getMediaUserIdentifier()->value);
    }

    public function testSetMediaFilesThrowsMissingFilesWhenFilesIsEmpty(): void
    {
        $this->expectException(MissingFiles::class);
        $this->expectExceptionMessage('Missing files from request');

        $this->buildEntity()->setMediaFiles(files: [], maxItems: 100, existingItems: 0, uniqueMimeTypes: []);
    }

    /**
     * @dataProvider maximumItemsDataProvider
     */
    public function testSetMediaFilesThrowsMaximumItemsReachedWhenLimitExceeded(
        int $maxItems,
        int $existingItems,
        int $newFiles
    ): void {
        $this->expectException(MaximumProfileItemsReachedException::class);

        $files = array_fill(0, $newFiles, $this->createMock(UploadedFile::class));
        $this->buildEntity()->setMediaFiles(
            files: $files,
            maxItems: $maxItems,
            existingItems: $existingItems,
            uniqueMimeTypes: ['image/jpeg']
        );
    }

    /**
     * @dataProvider unsupportedMimeTypesDataProvider
     */
    public function testSetMediaFilesThrowsIncorrectMimeTypeForUnsupportedTypes(array $mimeTypes): void
    {
        $this->expectException(IncorrectMimeTypeException::class);

        $files = array_fill(0, count($mimeTypes), $this->createMock(UploadedFile::class));
        $this->buildEntity()->setMediaFiles(
            files: $files,
            maxItems: 100,
            existingItems: 0,
            uniqueMimeTypes: $mimeTypes
        );
    }

    /**
     * @dataProvider acceptedMimeTypesDataProvider
     */
    public function testSetMediaFilesStoresFilesAndReturnsSelf(array $mimeTypes): void
    {
        $files = array_fill(0, count($mimeTypes), $this->createMock(UploadedFile::class));
        $entity = $this->buildEntity();

        $result = $entity->setMediaFiles(files: $files, maxItems: 100, existingItems: 0, uniqueMimeTypes: $mimeTypes);

        $this->assertSame($entity, $result);
        $this->assertSame($files, $entity->getMediaFiles());
    }

    public function testSetIsAdminReturnsFalseWhenSetToFalse(): void
    {
        $entity = $this->buildEntity();

        $result = $entity->setIsAdmin(false);

        $this->assertSame($entity, $result);
        $this->assertFalse($entity->getIsAdmin());
    }

    public function testSetIsAdminReturnsTrueWhenSetToTrue(): void
    {
        $entity = $this->buildEntity();

        $result = $entity->setIsAdmin(true);

        $this->assertSame($entity, $result);
        $this->assertTrue($entity->getIsAdmin());
    }

    /**
     * @dataProvider adminFilePathsDataProvider
     */
    public function testAddMediaPathsForDeletionStoresPathsAndReturnsSelfForAdmin(array $paths): void
    {
        $entity = $this
            ->buildEntity()
            ->setIsAdmin(true)
            ->setMediaUserIdentifier(new HashValueObject('client'))
            ->setIsAdmin(true);

        $result = $entity->addMediaPathsForDeletion(paths: $paths);

        $this->assertSame($entity, $result);
        $this->assertSame($paths, $entity->getMediaFilePathsForDeletion());
    }

    public function testAddMediaPathsForDeletionThrowsMediaFileDeletionNotAllowedForGuestWithForeignPath(): void
    {
        $this->expectException(MediaFileDeletionNotAllowedException::class);

        $this
            ->buildEntity()
            ->setIsAdmin(false)
            ->setMediaUserIdentifier(new HashValueObject('hashvalue'))
            ->addMediaPathsForDeletion(paths: ['other_user_hash/file.jpg']);
    }

    public function testAddMediaPathsForDeletionStoresPathsForGuestDeletingOwnFiles(): void
    {
        $entity = $this
            ->buildEntity()
            ->setIsAdmin(false)
            ->setMediaUserIdentifier(new HashValueObject('userId'))
            ->setIsAdmin(false);

        $paths = ['122/userId/aaa.jpg'];
        $result = $entity->addMediaPathsForDeletion(paths: $paths);

        $this->assertSame($entity, $result);
        $this->assertSame($paths, $entity->getMediaFilePathsForDeletion());
    }

    public function testSetAndGetStatus(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID), Status::VALID);

        $result = $entity->updateStatusForOrderStatus(new OrderStatusValueObject('completed'));

        $this->assertSame($entity, $result);
        $this->assertSame(Status::VALID, $entity->getStatus());
    }

    public function testSetAndGetUser(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID), Status::VALID);
        $user = new UserEntity(new EmailValueObject('user@example.com'));

        $result = $entity->setUser($user);

        $this->assertSame($entity, $result);
        $this->assertSame($user, $entity->getUser());
    }

    public function testSetAndGetOrderId(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID), Status::VALID);
        $orderId = new OrderIdValueObject(123);

        $result = $entity->setOrderId($orderId);

        $this->assertSame($entity, $result);
        $this->assertSame($orderId, $entity->getOrderId());
    }

    public function testSetAndGetEventName(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID), Status::VALID);
        $eventName = new ProfileNameValueObject('Summer Wedding');

        $result = $entity->setProfileName($eventName);

        $this->assertSame($entity, $result);
        $this->assertSame($eventName, $entity->getProfileName());
    }

    public function testSetAndGetEventNameFont(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID), Status::VALID);
        $font = new ProfileNameFontValueObject('elegant');

        $result = $entity->setProfileNameFont($font);

        $this->assertSame($entity, $result);
        $this->assertSame($font, $entity->getProfileNameFont());
    }

    public function testAddMediaPathsForDeletionThrowsMissingFilesWhenPathsAreEmpty(): void
    {
        $this->expectException(MissingFiles::class);

        $this->buildEntity()->setIsAdmin(true)->addMediaPathsForDeletion(paths: []);
    }

    public function testAddMediaPathsForDeletionThrowsGuestUsersCannotDeleteMultipleImagesException(): void
    {
        $this->expectException(GuestUsersCannotDeleteMultipleImagesException::class);

        $this
            ->buildEntity()
            ->setIsAdmin(false)
            ->setMediaUserIdentifier(new HashValueObject('userId'))
            ->addMediaPathsForDeletion(paths: ['123/userId/file1.jpg', '123/userId/file2.jpg']);
    }

    private function buildEntity(): ProfileEntity
    {
        return new ProfileEntity(profileUuidValueObject: new UuidValueObject(self::UUID), eventStatus: Status::VALID);
    }
}
