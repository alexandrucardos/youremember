<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileBackground;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\ProfileIdValueObject;

class UpdateProfileBackgroundHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(UpdateProfileBackgroundCommand $command): void
    {
        $profileId = $this->profileRepository->getExistingProfileIdForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileId === null || $profileId === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $profileEntity = new ProfileEntity(new ProfileIdValueObject($profileId));

        $profileEntity->setOrderId($command->orderIdValueObject)->setBackgroundFile($command->backgroundFile);

        $this->profileRepository->updateBackgroundFile($profileEntity);
    }
}
