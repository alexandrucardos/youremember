<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Application\ListEventInformation\ProfileViewModel;
use App\Application\ListEventInformation\MediaViewModel;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Entity\Event;
use App\Entity\Feedback;
use App\Entity\ImageArchive;
use App\Entity\User;
use App\Service\Bucket\BucketProviderInterface;
use App\Service\Event\EventMediaFetchService;
use App\Service\Event\EventService;
use App\Service\Event\EventUpdateService;
use App\Service\Media\MediaCountService;
use App\Service\Media\MediaDeleteService;
use App\Service\Media\MediaPresignService;
use App\Service\Media\MediaService;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;

class ProfileAggregateRepository implements ProfileRepositoryInterface
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly ImageArchiveRepository $imageArchiveRepository,
        private readonly UserRepository $userRepository,
        private readonly MediaService $mediaService,
        private readonly MediaCountService $mediaCountService,
        private readonly MediaDeleteService $mediaDeleteService,
        private readonly EventUpdateService $eventUpdateService,
        private readonly EventMediaFetchService $eventMediaFetchService,
        private readonly BucketProviderInterface $bucketProvider,
        private readonly MediaRepository $mediaRepository,
        private readonly MediaPresignService $mediaPresignService
    ) {
    }

    /**
     * @throws ProfileNotFoundException()
     */
    public function saveFeedbackForEvent(ProfileEntity $eventEntity): void
    {
        $event = $this->getEvent($eventEntity->eventUuidValueObject);

        $feedback = (new Feedback())
            ->setEvent($event)
            ->setFeedback($eventEntity->getFeedbackValueObject()->value);

        $this->feedbackRepository->save($feedback);
    }

    /**
     * @throws ProfileNotFoundException()
     */
    public function saveArchiveEmailForEvent(ProfileEntity $eventEntity): void
    {
        $event = $this->getEvent($eventEntity->eventUuidValueObject);

        $imageArchive = (new ImageArchive())
            ->setEvent($event)
            ->setEmail($eventEntity->getImageArchiveEmail()->value);

        $this->imageArchiveRepository->save($imageArchive);
    }

    public function fetchOrderIdForUuid(string $uuid): ?int
    {
        $event = $this->eventRepository->findOneBy(['uuid' => $uuid]);

        return $event ? $event->getOrderId() : null;
    }

    public function updateEventNameAndFont(ProfileEntity $eventEntity): void
    {
        $this->eventUpdateService->updateNameForEventUuid(
            eventUuid: $eventEntity->eventUuidValueObject,
            name: $eventEntity->getEventName(),
            nameFont: $eventEntity->getEventNameFont()
        );
    }

    public function fetchEventViewModelForEvent(OrderIdValueObject $orderIdValueObject): ProfileViewModel
    {
        $eventDataValueObject = $this->eventMediaFetchService->fetchForOrderId($orderIdValueObject);

        $event = $this->eventRepository->findOneBy(['order_id' => $orderIdValueObject->value]);

        return new ProfileViewModel(
            eventUuid: new UuidValueObject($event ? $event->getUuid() : null),
            eventName: $event->getName(),
            eventNameFont: new EventNameFontValueObject($event->getNameFont()),
            media: new MediaViewModel(
                backgroundPictureUrl: $eventDataValueObject->backgroundPictureUrl,
                picturesUrls: $eventDataValueObject->pictures
            )
        );
    }

    public function getExistingEventUuidAndStatus(UuidValueObject $uuidValueObject): array
    {
        $event = $this->getEvent($uuidValueObject);

        if (null === $event) {
            return [null, null];
        }

        return [$event->getUuid(), $event->getStatus()];
    }

    public function getExistingMediaInfo(UuidValueObject $uuidValueObject): array
    {
        $event = $this->getEvent($uuidValueObject);

        return [$event->getMaxMediaCount(), $event->getMediaCount()];
    }

    public function saveEvent(ProfileEntity $eventEntity): void
    {
        $user = $this->userRepository->findBy(['email' => $eventEntity->getUser()->emailValueObject->value]);

        $user = reset($user);

        if (empty($user)) {
            $user = new User();
            $user->setEmail($eventEntity->getUser()->emailValueObject->value)->setRole(UserRole::ROLE_ADMIN);

            $this->userRepository->save($user);
        }

        $event = (new Event())
            ->setStatus($eventEntity->getStatus())
            ->setUuid($eventEntity->eventUuidValueObject->value)
            ->setOrderId($eventEntity->getOrderId()->value)
            ->setNameFont($eventEntity->getEventNameFont()->value)
            ->setUser($user)
            ->setEventStartDate($eventEntity->getEventStartDate()->value)
            ->setNeedsManualProcessing($eventEntity->needsManualProcessing());

        $this->eventRepository->save($event);
    }

    public function getUniqueMimeTypes(array $uploadedFiles): array
    {
        $mimeTypes = [];

        foreach ($uploadedFiles as $uploadedFile) {
            $mimeType = $uploadedFile->getMimeType();

            $mimeTypes[$mimeType] = $mimeType;
        }

        return $mimeTypes;
    }

    public function saveMediaFiles(
        ProfileEntity $eventEntity,
        int $maxFileSizeBytes,
        int $maxTotalDemoSizeBytes
    ): void {
        $event = $this->getEvent($eventEntity->eventUuidValueObject);

        $this->mediaService->uploadMultiple(
            orderId: $event->getOrderId(),
            files: $eventEntity->getMediaFiles(),
            folder: $eventEntity->getMediaUserIdentifier()->value
        );

        //todo maybe add a lock on event

        $event->setMediaCount($event->getMediaCount() + count($eventEntity->getMediaFiles()));

        $this->eventRepository->save($event);
    }

    public function deleteMediaFiles(ProfileEntity $eventEntity): void
    {
        $urls = $eventEntity->getMediaFilePathsForDeletion();

        foreach ($urls as $url) {
            $this->mediaCountService->decrementByUrl($url);
            $this->mediaDeleteService->deleteByUrl($url);
        }
    }

    private function getEvent(UuidValueObject $uuidValueObject): Event
    {
        $event = $this->eventRepository->findOneBy([
            'uuid' => $uuidValueObject->value
        ]);

        if (!$event) {
            throw new ProfileNotFoundException();
        }

        return $event;
    }

    public function getExistingEventUuidForOrderId(OrderIdValueObject $orderIdValueObject): ?string
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderIdValueObject->value]);

        return $event ? $event->getUuid() : null;
    }

    public function updateEventStatus(ProfileEntity $eventEntity): void
    {
        $event = $this->eventRepository->findOneBy(['uuid' => $eventEntity->eventUuidValueObject->value]);

        if (!$event) {
            throw new ProfileNotFoundException();
        }

        $event->setStatus($eventEntity->getStatus());

        $this->eventRepository->save($event);
    }

    public function generateArchiveForOrder(OrderIdValueObject $orderIdValueObject): void
    {
        $paths = $this->mediaRepository->findPathsByOrderIdForDownload($orderIdValueObject->value);

        $paths = array_map(fn($path) => reset($path), $paths);

        if (empty($paths)) {
            return;
        }

        $this->bucketProvider->downloadFilesForPaths($paths);
    }

    public function updateBackgroundFile(ProfileEntity $eventEntity): void
    {
        $this->mediaService->uploadBackground(
            orderId: $eventEntity->getOrderId()->value,
            file: $eventEntity->getBackground()
        );
    }

    public function fetchMultipartInitData(ProfileEntity $eventEntity): array
    {
        return $this->mediaPresignService->initiateMultipartUpload(
            orderId: $eventEntity->getOrderId()->value,
            filename: $eventEntity->getMultipartFilename(),
            mimeType: $eventEntity->getMultipartMimeType(),
            folder: $eventEntity->getMediaUserIdentifier()->value
        );
    }
}
