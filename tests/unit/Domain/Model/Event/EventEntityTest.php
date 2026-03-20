<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\Event;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\Exception\MissingFiles;
use App\Domain\Model\User\UserEntity;
use App\Domain\Model\Profile\Message\DateInPastException;
use App\Domain\Model\Profile\Message\IncorrectMimeTypeException;
use App\Domain\Model\Profile\Message\MaximumProfileItemsReachedException;
use App\ValueObject\DateValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\ObituaryValueObject;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\ProfileNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EventEntityTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

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

        $entity = new ProfileEntity(profileUuidValueObject: $uuid);

        $this->assertSame($uuid, $entity->profileUuidValueObject);
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

    /**
     * @dataProvider adminFilePathsDataProvider
     */
    public function testAddMediaPathsForDeletionStoresPathsAndReturnsSelf(array $paths): void
    {
        $entity = $this->buildEntity();

        $result = $entity->addMediaPathsForDeletion(paths: $paths);

        $this->assertSame($entity, $result);
        $this->assertSame($paths, $entity->getMediaFilePathsForDeletion());
    }

    public function testSetAndGetUser(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $user = new UserEntity(new EmailValueObject('user@example.com'));

        $result = $entity->setUser($user);

        $this->assertSame($entity, $result);
        $this->assertSame($user, $entity->getUser());
    }

    public function testSetAndGetOrderId(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $orderId = new OrderIdValueObject(123);

        $result = $entity->setOrderId($orderId);

        $this->assertSame($entity, $result);
        $this->assertSame($orderId, $entity->getOrderId());
    }

    public function testSetAndGetEventName(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $eventName = new ProfileNameValueObject('Summer Wedding');

        $result = $entity->setProfileName($eventName);

        $this->assertSame($entity, $result);
        $this->assertSame($eventName, $entity->getProfileName());
    }

    public function testSetAndGetEventNameFont(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $font = new ProfileNameFontValueObject('elegant');

        $result = $entity->setProfileNameFont($font);

        $this->assertSame($entity, $result);
        $this->assertSame($font, $entity->getProfileNameFont());
    }

    public function testAddMediaPathsForDeletionThrowsMissingFilesWhenPathsAreEmpty(): void
    {
        $this->expectException(MissingFiles::class);

        $this->buildEntity()->addMediaPathsForDeletion(paths: []);
    }

    public function testSetAndGetBornAt(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $bornDate = new DateValueObject(new \DateTimeImmutable('1990-01-01'));

        $result = $entity->setBornAt($bornDate);

        $this->assertSame($entity, $result);
        $this->assertSame($bornDate, $entity->getBornAt());
    }

    public function testSetBornAtThrowsExceptionWhenDateInFuture(): void
    {
        $this->expectException(DateInPastException::class);
        $this->expectExceptionMessage('Born date must be in the past');

        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $futureDate = new DateValueObject(new \DateTimeImmutable('+1 year'));

        $entity->setBornAt($futureDate);
    }

    public function testSetAndGetDepartedAt(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $departedDate = new DateValueObject(new \DateTimeImmutable('2020-12-31'));

        $result = $entity->setDepartedAt($departedDate);

        $this->assertSame($entity, $result);
        $this->assertSame($departedDate, $entity->getDepartedAt());
    }

    public function testSetDepartedAtThrowsExceptionWhenDateInFuture(): void
    {
        $this->expectException(DateInPastException::class);
        $this->expectExceptionMessage('Deceased date must be in the past');

        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $futureDate = new DateValueObject(new \DateTimeImmutable('+1 year'));

        $entity->setDepartedAt($futureDate);
    }

    public function testSetAndGetObituary(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $obituary = new ObituaryValueObject('In loving memory...');

        $result = $entity->setObituary($obituary);

        $this->assertSame($entity, $result);
        $this->assertSame($obituary, $entity->getObituary());
    }

    public function testSetAndGetBackgroundFile(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $backgroundFile = $this->createMock(UploadedFile::class);

        $result = $entity->setBackgroundFile($backgroundFile);

        $this->assertSame($entity, $result);
        $this->assertSame($backgroundFile, $entity->getBackground());
    }

    public function testSetAndGetProfilePictureFile(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $profilePicture = $this->createMock(UploadedFile::class);

        $result = $entity->setProfilePictureFile($profilePicture);

        $this->assertSame($entity, $result);
        $this->assertSame($profilePicture, $entity->getProfilePicture());
    }

    public function testSetAndGetMultipartFilename(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $filename = 'video.mp4';

        $result = $entity->setMultipartFilename($filename);

        $this->assertSame($entity, $result);
        $this->assertSame($filename, $entity->getMultipartFilename());
    }

    public function testSetAndGetMultipartMimeType(): void
    {
        $entity = new ProfileEntity(new UuidValueObject(self::UUID));
        $mimeType = 'video/mp4';

        $result = $entity->setMultipartMimeType($mimeType);

        $this->assertSame($entity, $result);
        $this->assertSame($mimeType, $entity->getMultipartMimeType());
    }

    private function buildEntity(): ProfileEntity
    {
        return new ProfileEntity(profileUuidValueObject: new UuidValueObject(self::UUID));
    }
}
