<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Application\ListProfileInformation\MediaViewModel;
use App\Application\ListProfileInformation\ProfileViewModel;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Entity\Profile;
use App\Entity\User;
use App\Service\Event\EventMediaFetchService;
use App\Service\Event\EventUpdateService;
use App\Service\Media\MediaCountService;
use App\Service\Media\MediaDeleteService;
use App\Service\Media\MediaPresignService;
use App\Service\Media\MediaService;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\ProfileIdValueObject;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\UserRole;

class ProfileAdapterRepository implements ProfileRepositoryInterface
{
    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly UserRepository $userRepository,
        private readonly MediaService $mediaService,
        private readonly MediaCountService $mediaCountService,
        private readonly MediaDeleteService $mediaDeleteService,
        private readonly EventUpdateService $eventUpdateService,
        private readonly EventMediaFetchService $eventMediaFetchService,
        private readonly MediaRepository $mediaRepository,
        private readonly MediaPresignService $mediaPresignService
    ) {
    }

    public function updateProfileNameAndFont(ProfileEntity $eventEntity): void
    {
        $this->eventUpdateService->updateNameForEventUuid(
            eventUuid: $eventEntity->profileIdValueObject,
            name: $eventEntity->getProfileName(),
            nameFont: $eventEntity->getProfileNameFont()
        );
    }

    public function updateProfileDates(ProfileEntity $profileEntity): void
    {
        $profile = $this->profileRepository->findOneBy(['uuid' => $profileEntity->profileIdValueObject->value]);

        if (!$profile) {
            throw new ProfileNotFoundException();
        }

        $profile->setBornAt($profileEntity->getBornAt()->value)->setDepartedAt($profileEntity->getDepartedAt()->value);

        $this->profileRepository->save($profile);
    }

    public function updateProfileObituary(ProfileEntity $profileEntity): void
    {
        $profile = $this->profileRepository->findOneBy(['uuid' => $profileEntity->profileIdValueObject->value]);

        if (!$profile) {
            throw new ProfileNotFoundException();
        }

        $profile->setObituary($profileEntity->getObituary()->value);

        $this->profileRepository->save($profile);
    }

    public function fetchEventViewModelForEvent(OrderIdValueObject $orderIdValueObject): ProfileViewModel
    {
        $eventDataValueObject = $this->eventMediaFetchService->fetchForOrderId($orderIdValueObject);

        $event = $this->profileRepository->findOneBy(['order_id' => $orderIdValueObject->value]);

        return new ProfileViewModel(
            eventUuid: new ProfileIdValueObject($event ? $event->getUuid() : null),
            eventName: $event->getName(),
            eventNameFont: new ProfileNameFontValueObject($event->getNameFont()),
            media: new MediaViewModel(
                backgroundPictureUrl: $eventDataValueObject['backgroundPictureUrl'],
                picturesUrls: $eventDataValueObject['pictures']
            )
        );
    }

    public function getExistingMediaInfo(OrderIdValueObject $orderId): array
    {
        $event = $this->getEvent($orderId);

        return [$event->getMaxMediaCount(), $event->getMediaCount()];
    }

    public function saveProfile(ProfileEntity $profileEntity): void
    {
        $user = $this->userRepository->findBy(['email' => $profileEntity->getUser()->emailValueObject->value]);

        $user = reset($user);

        if (empty($user)) {
            $user = new User();
            $user->setEmail($profileEntity->getUser()->emailValueObject->value)->setRole(UserRole::ROLE_ADMIN);

            $this->userRepository->save($user);
        }

        $event = (new Profile())
            ->setUuid($profileEntity->profileIdValueObject->value)
            ->setOrderId($profileEntity->getOrderId()->value)
            ->setNameFont($profileEntity->getProfileNameFont()->value)
            ->setUser($user);

        $this->profileRepository->save($event);
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
        int $maxFileSizeBytes
    ): void {
        $event = $this->getEvent($eventEntity->profileIdValueObject);

        $this->mediaService->uploadMultiple(orderId: $event->getOrderId(), files: $eventEntity->getMediaFiles());

        //todo maybe add a lock on event

        $event->setMediaCount($event->getMediaCount() + count($eventEntity->getMediaFiles()));

        $this->profileRepository->save($event);
    }

    public function deleteMediaFiles(ProfileEntity $eventEntity): void
    {
        $urls = $eventEntity->getMediaFilePathsForDeletion();

        foreach ($urls as $url) {
            $this->mediaCountService->decrementByUrl($url);
            $this->mediaDeleteService->deleteByUrl($url);
        }
    }

    public function getExistingProfileIdForOrderIdAndEmail(
        OrderIdValueObject $orderIdValueObject,
        EmailValueObject $emailValueObject
    ): ?string {
        $event = $this->profileRepository->findOneBy(['order_id' => $orderIdValueObject->value]);

        return $event ? $event->getUuid() : null;
    }

    public function updateBackgroundFile(ProfileEntity $eventEntity): void
    {
        $this->mediaService->uploadBackground(
            orderId: $eventEntity->getOrderId()->value,
            file: $eventEntity->getBackground()
        );
    }

    public function updateProfilePictureFile(ProfileEntity $eventEntity): void
    {
        $this->mediaService->uploadProfilePicture(
            orderId: $eventEntity->getOrderId()->value,
            file: $eventEntity->getProfilePicture()
        );
    }

    public function fetchMultipartInitData(ProfileEntity $eventEntity): array
    {
        return $this->mediaPresignService->initiateMultipartUpload(
            orderId: $eventEntity->getOrderId()->value,
            filename: $eventEntity->getMultipartFilename(),
            mimeType: $eventEntity->getMultipartMimeType()
        );
    }

    public function verifyExistingProfileId(): ?int
    {
        // TODO: Implement getLastProfileId() method.
    }

    private function getEvent(ProfileIdValueObject $uuidValueObject): Profile
    {
        $event = $this->profileRepository->findOneBy([
            'uuid' => $uuidValueObject->value
        ]);

        if (!$event) {
            throw new ProfileNotFoundException();
        }

        return $event;
    }
}
