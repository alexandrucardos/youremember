<?php

declare(strict_types = 1);

namespace App\Application\AddMedia;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\UuidValueObject;

final class AddMediaHandler
{
    public const MAX_IMAGE_SIZE_BYTES = 10_485_760;
    public const MAX_TOTAL_DEMO_SIZE_BYTES = 10_485_760;

    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(AddMediaCommand $command): void
    {
        $uuid = $this->eventRepository->getExistingProfileUuidForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($uuid === null || $uuid === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        [$maxItems, $existingItems] = $this->eventRepository->getExistingMediaInfo($command->orderIdValueObject);

        $uniqueMimeTypes = $this->eventRepository->getUniqueMimeTypes($command->files);

        $eventEntity = new ProfileEntity(new UuidValueObject($uuid));

        $eventEntity->setMediaFiles($command->files, $maxItems, $existingItems, $uniqueMimeTypes);

        $this->eventRepository->saveMediaFiles(
            $eventEntity,
            self::MAX_IMAGE_SIZE_BYTES,
            self::MAX_TOTAL_DEMO_SIZE_BYTES
        );
    }
}
