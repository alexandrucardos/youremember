<?php

declare(strict_types = 1);

namespace App\Domain\Model\Event;

use App\Domain\Model\Event\Exception\GuestUsersCannotDeleteMultipleImagesException;
use App\Domain\Model\Event\Exception\MediaFileDeletionNotAllowedException;
use App\Domain\Model\Event\Exception\MissingFiles;
use App\Domain\Model\Event\Exception\OnlyAdminsCanSetBackgroundsException;
use App\Domain\Model\Event\Exception\OrderStatusInvalidException;
use App\Domain\Model\Event\Message\DateTooSoonException;
use App\Domain\Model\Event\Message\EventNotValidException;
use App\Domain\Model\Event\Message\IncorrectMimeTypeException;
use App\Domain\Model\Event\Message\MaximumEventItemsReachedException;
use App\Domain\Model\User\UserEntity;
use App\Domain\ValueObject\EventStartDateValueObject;
use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\HashValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use App\ValueObject\Status;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EventEntity
{
    private const ACCEPTED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'video/mp4',
        'video/webm'
    ];

    private const EVENT_START_DATE_INTERVAL_FOR_MANUAL_PROCESSING_ORDERS = 14;
    const ADMIN_USER_IDENTIFIER = 'admin';
    const DEFAULT_EVENT_NAME_FONT = 'classic';

    /**
     * @var array<UploadedFile>
     */
    private array $mediaFiles;
    private HashValueObject $mediaUserIdentifier;
    private bool $isAdmin;
    private array $mediaFilePathsForDeletion;
    private FeedbackValueObject $feedbackValueObject;
    private EmailValueObject $imageArchiveEmail;
    private ?Status $status = null;
    private UserEntity $user;
    private OrderIdValueObject $orderId;
    private EventNameValueObject $eventName;
    private EventNameFontValueObject $eventNameFont;
    private EventStartDateValueObject $eventStartDate;

    private string $multipartFilename;
    private string $multipartMimeType;

    private bool $needsManualProcessing;
    private UploadedFile $backgroundFile;

    public function __construct(
        public readonly UuidValueObject $eventUuidValueObject,
        public readonly Status $eventStatus
    ) {
        if ($this->eventStatus !== Status::VALID) {
            throw new EventNotValidException('Event is not valid');
        }
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function updateStatusForOrderStatus(OrderStatusValueObject $orderStatus): self
    {
        if ($orderStatus->value === 'processing') {
            $this->status = Status::INVALID;
            return $this;
        }

        if ($orderStatus->value === 'completed') {
            $this->status = Status::VALID;
            return $this;
        }

        throw new OrderStatusInvalidException('Order status is not valid');
    }

    public function getMediaUserIdentifier(): HashValueObject
    {
        return $this->mediaUserIdentifier;
    }

    public function setMediaUserIdentifier(HashValueObject $mediaUserIdentifier): self
    {
        if ($this->isAdmin) {
            $this->mediaUserIdentifier = new HashValueObject(self::ADMIN_USER_IDENTIFIER);

            return $this;
        }
        $this->mediaUserIdentifier = $mediaUserIdentifier;

        return $this;
    }

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(
        UserRole $userRole
    ): self
    {
        if (in_array($userRole, UserRole::getAdminRoles(), true)) {
            $this->isAdmin = true;
        } else {
            $this->isAdmin = false;
        }

        return $this;
    }

    public function addMediaPathsForDeletion(
        array $paths
    ): self
    {
        if (empty($paths)) {
            throw new MissingFiles('Missing files');
        }

        if ($this->isAdmin) {
            $this->mediaFilePathsForDeletion = $paths;

            return $this;
        }

        if (count($paths) > 1) {
            throw new GuestUsersCannotDeleteMultipleImagesException('Only one media file can be deleted');
        }

        foreach ($paths as $path) {
            if (!strpos($path, $this->getMediaUserIdentifier()->value)) {
                throw new MediaFileDeletionNotAllowedException('Media file deletion not allowed!');
            }
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
            throw new MaximumEventItemsReachedException('Maximum number of files reached!');
        }

        //todo add validation for total size limit reached, for demo

        if (!empty(array_diff($uniqueMimeTypes, self::ACCEPTED_MIME_TYPES))) {
            throw new IncorrectMimeTypeException('Incorrect media type!');
        }

        $this->mediaFiles = $files;

        return $this;
    }

    public function getFeedbackValueObject(): FeedbackValueObject
    {
        return $this->feedbackValueObject;
    }

    public function setFeedbackValueObject(FeedbackValueObject $feedbackValueObject): void
    {
        $this->feedbackValueObject = $feedbackValueObject;
    }

    public function getImageArchiveEmail(): EmailValueObject
    {
        return $this->imageArchiveEmail;
    }

    public function setImageArchiveEmail(EmailValueObject $imageArchiveEmail): void
    {
        $this->imageArchiveEmail = $imageArchiveEmail;
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
    public function getEventName(): EventNameValueObject
    {
        return $this->eventName;
    }

    public function setEventName(EventNameValueObject $eventName): self
    {
        $this->eventName = $eventName;

        return $this;
    }

    public function setEventNameFont(EventNameFontValueObject $eventNameFontValueObject): self
    {
        $this->eventNameFont = $eventNameFontValueObject;
        return $this;
    }

    public function getEventNameFont(): EventNameFontValueObject
    {
        return $this->eventNameFont;
    }

    public function getEventStartDate(): EventStartDateValueObject
    {
        return $this->eventStartDate;
    }

    public function setEventStartDate(EventStartDateValueObject $eventStartDate): self
    {
        if ($eventStartDate->value <= new \DateTimeImmutable('now')) {
            throw new DateTooSoonException('Event start date is too soon!');
        }

        if (
            $this->needsManualProcessing()
            && $eventStartDate->value <= new \DateTimeImmutable(sprintf(
                'now +%d days',
                self::EVENT_START_DATE_INTERVAL_FOR_MANUAL_PROCESSING_ORDERS
            ))
        ) {
            throw new DateTooSoonException('Event start date is too soon!');
        }

        $this->eventStartDate = $eventStartDate;

        return $this;
    }

    public function needsManualProcessing(): bool
    {
        return $this->needsManualProcessing;
    }

    public function setNeedsManualProcessing(bool $needsManualProcessing): self
    {
        $this->needsManualProcessing = $needsManualProcessing;

        return $this;
    }

    public function setBackgroundFile(UploadedFile $background): self
    {
        if ($this->getIsAdmin()) {
            $this->backgroundFile = $background;
            return $this;
        }

        throw new OnlyAdminsCanSetBackgroundsException();
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
