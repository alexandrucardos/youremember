<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile;

use App\Domain\Model\Profile\Exception\MissingFiles;
use App\Domain\Model\Profile\Message\DateInPastException;
use App\Domain\Model\Profile\Message\IncorrectMimeTypeException;
use App\Domain\Model\Profile\Message\MaximumProfileItemsReachedException;
use App\Domain\Model\User\UserEntity;
use App\Domain\ValueObject\DateValueObject;
use App\Domain\ValueObject\ObituaryValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;
use App\Domain\ValueObject\ProfileNameValueObject;
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
    private DateValueObject $bornAt;
    private DateValueObject $departedAt;
    private ObituaryValueObject $obituary;

    private string $multipartFilename;
    private string $multipartMimeType;

    private UploadedFile $backgroundFile;
    private UploadedFile $profilePictureFile;

    public function __construct(
        public readonly ProfileIdValueObject $profileIdValueObject
    ) {
    }

    public function addMediaPathsForDeletion(
        array $paths
    ): self
    {
        if (empty($paths)) {
            throw new MissingFiles('Nu exista fisiere selectate');
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
            throw new MissingFiles('Nu exista fisiere selectate');
        }

        if ($maxItems < ( $existingItems + count($files) )) {
            throw new MaximumProfileItemsReachedException('Numar maxim de fisiere atins!');
        }

        if (!empty(array_diff($uniqueMimeTypes, self::ACCEPTED_MIME_TYPES))) {
            throw new IncorrectMimeTypeException('Tip de fisier invalid!');
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

    public function getProfileNameFont(): ProfileNameFontValueObject
    {
        return $this->profileNameFont;
    }

    public function setProfileNameFont(ProfileNameFontValueObject $eventNameFontValueObject): self
    {
        $this->profileNameFont = $eventNameFontValueObject;
        return $this;
    }

    public function getBornAt(): DateValueObject
    {
        return $this->bornAt;
    }

    public function setBornAt(DateValueObject $bornAtValueObject): self
    {
        if ($bornAtValueObject->value instanceof \DateTimeImmutable) {
            $now = new \DateTimeImmutable();
            if ($bornAtValueObject->value > $now) {
                throw new DateInPastException('Data nasterii treuie sa fie in trecut');
            }
        }

        $this->bornAt = $bornAtValueObject;
        return $this;
    }

    public function getDepartedAt(): DateValueObject
    {
        return $this->departedAt;
    }

    public function setDepartedAt(DateValueObject $departedAtValueObject): self
    {
        if ($departedAtValueObject->value instanceof \DateTimeImmutable) {
            $now = new \DateTimeImmutable();
            if ($departedAtValueObject->value >= $now) {
                throw new DateInPastException('Data plecarii trebuie sa fie in trecut');
            }
        }

        $this->departedAt = $departedAtValueObject;
        return $this;
    }

    public function getObituary(): ObituaryValueObject
    {
        return $this->obituary;
    }

    public function setObituary(ObituaryValueObject $obituaryValueObject): self
    {
        $this->obituary = $obituaryValueObject;
        return $this;
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

    public function setProfilePictureFile(UploadedFile $profilePicture): self
    {
        $this->profilePictureFile = $profilePicture;
        return $this;
    }

    public function getProfilePicture(): UploadedFile
    {
        return $this->profilePictureFile;
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
