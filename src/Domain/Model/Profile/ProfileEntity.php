<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile;

use App\Domain\Model\Profile\Exception\GuestUsersCannotDeleteMultipleImagesException;
use App\Domain\Model\Profile\Exception\MediaFileDeletionNotAllowedException;
use App\Domain\Model\Profile\Exception\MissingFiles;
use App\Domain\Model\Profile\Exception\OnlyAdminsCanSetBackgroundsException;
use App\Domain\Model\Profile\Exception\OrderStatusInvalidException;
use App\Domain\Model\Profile\Message\DateTooSoonException;
use App\Domain\Model\Profile\Message\ProfileNotValidException;
use App\Domain\Model\Profile\Message\IncorrectMimeTypeException;
use App\Domain\Model\Profile\Message\MaximumProfileItemsReachedException;
use App\Domain\Model\User\UserEntity;
use App\Domain\ValueObject\EventStartDateValueObject;
use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\ProfileNameValueObject;
use App\ValueObject\HashValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use App\ValueObject\Status;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileEntity
{
    private const ACCEPTED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'video/mp4',
        'video/webm'
    ];

    const ADMIN_USER_IDENTIFIER = 'admin';
    const DEFAULT_EVENT_NAME_FONT = 'classic';

    /**
     * @var array<UploadedFile>
     */
    private array $mediaFiles;
    private array $mediaFilePathsForDeletion;
    private UserEntity $user;
    private OrderIdValueObject $orderId;
    private ProfileNameValueObject $profileName;
    private ProfileNameFontValueObject $profileNameFont;

    private string $multipartFilename;
    private string $multipartMimeType;

    private UploadedFile $backgroundFile;

    public function __construct(
        public readonly UuidValueObject $profileUuidValueObject
    ) {
    }

    public function addMediaPathsForDeletion(
        array $paths
    ): self
    {
        if (empty($paths)) {
            throw new MissingFiles('Missing files');
        }

        $this->mediaFilePathsForDeletion = $paths;

        return $this;
    }

    public function getMediaFilePathsForDeletion(): array
    {
        return $this->mediaFilePathsForDeletion;
    }

    public function getMediaFiles(): array
    {
        return $this->mediaFiles;
    }

    public function setMediaFiles(
        array $files,
        int $maxItems,
        int $existingItems,
        array $uniqueMimeTypes
    ): self {
        if (empty($files)) {
            throw new MissingFiles('Missing files from request');
        }

        if ($maxItems < ( $existingItems + count($files) )) {
            throw new MaximumProfileItemsReachedException('Maximum number of files reached!');
        }

        if (!empty(array_diff($uniqueMimeTypes, self::ACCEPTED_MIME_TYPES))) {
            throw new IncorrectMimeTypeException('Incorrect media type!');
        }

        $this->mediaFiles = $files;

        return $this;
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function setUser(UserEntity $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getOrderId(): OrderIdValueObject
    {
        return $this->orderId;
    }

    public function setOrderId(OrderIdValueObject $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProfileName(): ProfileNameValueObject
    {
        return $this->profileName;
    }

    public function setProfileName(ProfileNameValueObject $profileName): self
    {
        $this->profileName = $profileName;

        return $this;
    }

    public function setProfileNameFont(ProfileNameFontValueObject $eventNameFontValueObject): self
    {
        $this->profileNameFont = $eventNameFontValueObject;
        return $this;
    }

    public function getProfileNameFont(): ProfileNameFontValueObject
    {
        return $this->profileNameFont;
    }

    public function setBackgroundFile(UploadedFile $background): self
    {
        $this->backgroundFile = $background;
        return $this;
    }

    public function getBackground(): UploadedFile
    {
        return $this->backgroundFile;
    }

    public function getMultipartFilename(): string
    {
        return $this->multipartFilename;
    }

    public function setMultipartFilename(string $multipartFilename): self
    {
        $this->multipartFilename = $multipartFilename;
        return $this;
    }

    public function getMultipartMimeType(): string
    {
        return $this->multipartMimeType;
    }

    public function setMultipartMimeType(string $multipartMimeType): self
    {
        $this->multipartMimeType = $multipartMimeType;
        return $this;
    }
}
