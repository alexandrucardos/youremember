<?php

declare(strict_types = 1);

namespace App\Domain\Model\Event;

use App\Application\ListEventInformation\EventViewModel;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface EventRepositoryInterface
{
    /**
     * @throws EventNotFoundException()
     */
    public function saveFeedbackForEvent(EventEntity $eventEntity): void;

    /**
     * @throws EventNotFoundException()
     */
    public function saveArchiveEmailForEvent(EventEntity $eventEntity): void;

    public function fetchOrderIdForUuid(string $uuid): ?int;

    public function updateEventNameAndFont(EventEntity $eventEntity): void;

    public function fetchEventViewModelForEvent(OrderIdValueObject $orderIdValueObject): EventViewModel;

    public function getExistingEventUuidAndStatus(UuidValueObject $uuidValueObject): array;

    public function getExistingEventUuidForOrderId(OrderIdValueObject $orderIdValueObject): ?string;

    public function getExistingMediaInfo(UuidValueObject $uuidValueObject): array;

    public function saveEvent(EventEntity $eventEntity): void;

    public function saveMediaFiles(
        EventEntity $eventEntity,
        int $maxFileSizeBytes,
        int $maxTotalDemoSizeBytes
    ): void;

    /**
     * @param array<UploadedFile> $uploadedFiles
     */
    public function getUniqueMimeTypes(array $uploadedFiles): array;

    public function deleteMediaFiles(EventEntity $eventEntity): void;

    public function updateEventStatus(EventEntity $eventEntity): void;

    public function generateArchiveForOrder(OrderIdValueObject $orderIdValueObject): void;

    public function updateBackgroundFile(EventEntity $eventEntity): void;

    public function fetchMultipartInitData(EventEntity $eventEntity): array;
}
