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
use App\Service\Event\ProfileMediaFetchService;
use App\Service\Event\ProfileUpdateService;
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
        private readonly ProfileUpdateService $eventUpdateService,
        private readonly ProfileMediaFetchService $profileMediaFetchService,
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
        $profile = $this->profileRepository->findOneBy(['external_id' => $profileEntity->profileIdValueObject->value]);

        if (!$profile) {
            throw new ProfileNotFoundException();
        }

        $profile->setBornAt($profileEntity->getBornAt()->value)->setDepartedAt($profileEntity->getDepartedAt()->value);

        $this->profileRepository->save($profile);
    }

    public function updateProfileObituary(ProfileEntity $profileEntity): void
    {
        $profile = $this->profileRepository->findOneBy(['external_id' => $profileEntity->profileIdValueObject->value]);

        if (!$profile) {
            throw new ProfileNotFoundException();
        }

        $profile->setObituary($profileEntity->getObituary()->value);

        $this->profileRepository->save($profile);
    }

    public function fetchProfileViewModelForOrderId(OrderIdValueObject $orderIdValueObject): ProfileViewModel
    {
        $profileDataValueObject = $this->profileMediaFetchService->fetchForOrderId($orderIdValueObject);

        $profile = $this->profileRepository->findOneBy(['order_id' => $orderIdValueObject->value]);

        return new ProfileViewModel(
            profileId: new ProfileIdValueObject($profile ? $profile->getExternalId() : null),
            profileName: $profile->getName(),
            profileNameFont: new ProfileNameFontValueObject($profile->getNameFont()),
            media: new MediaViewModel(
                backgroundPictureUrl: $profileDataValueObject['backgroundPictureUrl'],
                profilePictureUrl: $profileDataValueObject['profilePictureUrl'],
                picturesUrls: $profileDataValueObject['pictures']
            )
        );
    }

    public function getExistingMediaInfo(OrderIdValueObject $orderId): array
    {
        $event = $this->getProfile($orderId);

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

        $profile = (new Profile())
            ->setExternalId($profileEntity->profileIdValueObject->value)
            ->setOrderId($profileEntity->getOrderId()->value)
            ->setNameFont($profileEntity->getProfileNameFont()->value)
            ->setUser($user);

        $this->profileRepository->save($profile);
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
        $profile = $this->getProfile($eventEntity->getOrderId());

        $this->mediaService->uploadMultiple(orderId: $profile->getOrderId(), files: $eventEntity->getMediaFiles());

        //todo maybe add a lock on event

        $profile->setMediaCount($profile->getMediaCount() + count($eventEntity->getMediaFiles()));

        $this->profileRepository->save($profile);
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
        $profile = $this->profileRepository->findOneBy(['order_id' => $orderIdValueObject->value]);

        return $profile ? (string) $profile->getExternalId() : null;
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

    public function getExistingProfileId(ProfileIdValueObject $profileIdValueObject): ?int
    {
        $profile = $this->profileRepository->findOneBy([
            'external_id' => $profileIdValueObject->value
        ]);

        return $profile ? $profile->getExternalId() : null;
    }

    private function getProfile(OrderIdValueObject $orderIdValueObject): Profile
    {
        $profile = $this->profileRepository->findOneBy([
            'order_id' => $orderIdValueObject->value
        ]);

        if (!$profile) {
            throw new ProfileNotFoundException();
        }

        return $profile;
    }
}
