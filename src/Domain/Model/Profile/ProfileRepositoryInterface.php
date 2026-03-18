<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile;

use App\Application\ListEventInformation\ProfileViewModel;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ProfileRepositoryInterface
{
    /**
     * @throws ProfileNotFoundException()
     */
    public function saveFeedbackForEvent(ProfileEntity $eventEntity): void;

    /**
     * @throws ProfileNotFoundException()
     */
    public function saveArchiveEmailForEvent(ProfileEntity $eventEntity): void;

    public function fetchOrderIdForUuid(string $uuid): ?int;

    public function updateEventNameAndFont(ProfileEntity $eventEntity): void;

    public function fetchEventViewModelForEvent(OrderIdValueObject $orderIdValueObject): ProfileViewModel;

    public function getExistingEventUuidAndStatus(UuidValueObject $uuidValueObject): array;

    public function getExistingEventUuidForOrderId(OrderIdValueObject $orderIdValueObject): ?string;

    public function getExistingMediaInfo(UuidValueObject $uuidValueObject): array;

    public function saveEvent(ProfileEntity $eventEntity): void;

    public function saveMediaFiles(
        ProfileEntity $eventEntity,
        int $maxFileSizeBytes,
        int $maxTotalDemoSizeBytes
    ): void;

    /**
     * @param array<UploadedFile> $uploadedFiles
     */
    public function getUniqueMimeTypes(array $uploadedFiles): array;

    public function deleteMediaFiles(ProfileEntity $eventEntity): void;

    public function updateEventStatus(ProfileEntity $eventEntity): void;

    public function generateArchiveForOrder(OrderIdValueObject $orderIdValueObject): void;

    public function updateBackgroundFile(ProfileEntity $eventEntity): void;

    public function fetchMultipartInitData(ProfileEntity $eventEntity): array;
}
