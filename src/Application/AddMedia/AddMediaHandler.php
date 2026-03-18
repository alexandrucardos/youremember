<?php

declare(strict_types = 1);

namespace App\Application\AddMedia;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;

final class AddMediaHandler
{
    public const MAX_IMAGE_SIZE_BYTES = 10_485_760;
    public const MAX_TOTAL_DEMO_SIZE_BYTES = 10_485_760;

    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(AddMediaCommand $command): void
    {
        [$uuid, $status] = $this->eventRepository->getExistingEventUuidAndStatus($command->eventUuidValueObject);

        if ($uuid === null || $uuid === '') {
            throw new EventNotFoundException('Event not found');
        }

        [$maxItems, $existingItems] = $this->eventRepository->getExistingMediaInfo($command->eventUuidValueObject);

        $uniqueMimeTypes = $this->eventRepository->getUniqueMimeTypes($command->files);

        $eventEntity = new EventEntity(new UuidValueObject($uuid), $status);

        $eventEntity
            ->setIsAdmin($command->userRole)
            ->setMediaUserIdentifier($command->userIdentifier)
            ->setMediaFiles($command->files, $maxItems, $existingItems, $uniqueMimeTypes);

        $this->eventRepository->saveMediaFiles(
            $eventEntity,
            self::MAX_IMAGE_SIZE_BYTES,
            self::MAX_TOTAL_DEMO_SIZE_BYTES
        );
    }
}
