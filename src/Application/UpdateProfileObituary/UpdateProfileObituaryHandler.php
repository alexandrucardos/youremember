<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileObituary;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\ProfileIdValueObject;

class UpdateProfileObituaryHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(UpdateProfileObituaryCommand $command): void
    {
        $profileId = $this->profileRepository->getExistingProfileIdForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileId === null || $profileId === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $profileEntity = new ProfileEntity(profileIdValueObject: new ProfileIdValueObject($profileId));

        $profileEntity->setObituary($command->obituaryValueObject);

        $this->profileRepository->updateProfileObituary($profileEntity);
    }
}
