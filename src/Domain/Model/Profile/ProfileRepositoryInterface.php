<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile;

use App\Application\ListProfileInformation\ListProfileViewModel;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileIdValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ProfileRepositoryInterface
{
    public function updateProfileNameAndFont(ProfileEntity $profileEntity): void;

    public function updateProfileNameTitleAndFont(ProfileEntity $profileEntity): void;

    public function updateProfileDates(ProfileEntity $profileEntity): void;

    public function updateProfileObituary(ProfileEntity $profileEntity): void;

    public function fetchProfileViewModelForOrderId(int $orderId): ListProfileViewModel;

    public function getExistingProfileIdForOrderIdAndEmail(
        OrderIdValueObject $orderIdValueObject,
        EmailValueObject $emailValueObject
    ): ?string;

    public function getExistingMediaInfo(OrderIdValueObject $orderId): array;

    public function saveProfile(ProfileEntity $profileEntity): void;

    public function saveMediaFiles(
        ProfileEntity $eventEntity,
        int $maxFileSizeBytes
    ): void;

    /**
     * @param array<UploadedFile> $uploadedFiles
     */
    public function getUniqueMimeTypes(array $uploadedFiles): array;

    public function deleteMediaFiles(ProfileEntity $profileEntity): void;

    public function updateBackgroundFile(ProfileEntity $profileEntity): void;

    public function updateProfilePictureFile(ProfileEntity $profileEntity): void;

    public function fetchMultipartInitData(ProfileEntity $profileEntity): array;

    public function getExistingProfileId(ProfileIdValueObject $profileIdValueObject): ?int;

    public function getExistingProfileIdForOrderId(OrderIdValueObject $orderIdValueObject): ?int;

    public function getExistingOrderIdForProfileId(ProfileIdValueObject $profileIdValueObject): ?int;
}
