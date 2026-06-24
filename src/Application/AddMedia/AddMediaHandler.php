<?php

declare(strict_types = 1);

namespace App\Application\AddMedia;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\ProfileIdValueObject;

final class AddMediaHandler
{
    public const MAX_IMAGE_SIZE_BYTES = 10_485_760;
    public const MAX_TOTAL_DEMO_SIZE_BYTES = 10_485_760;

    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(AddMediaCommand $command): void
    {
        $profileId = $this->profileRepository->getExistingProfileIdForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileId === null || $profileId === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        [$maxItems, $existingItems] = $this->profileRepository->getExistingMediaInfo($command->orderIdValueObject);

        $uniqueMimeTypes = $this->profileRepository->getUniqueMimeTypes($command->files);

        $profileEntity = new ProfileEntity(new ProfileIdValueObject($profileId));

        $profileEntity
            ->setMediaFiles($command->files, $maxItems, $existingItems, $uniqueMimeTypes)
            ->setOrderId($command->orderIdValueObject);

        $this->profileRepository->saveMediaFiles($profileEntity, self::MAX_IMAGE_SIZE_BYTES);
    }
}
