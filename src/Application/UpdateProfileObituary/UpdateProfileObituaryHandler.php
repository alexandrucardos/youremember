<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileObituary;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\UuidValueObject;

class UpdateProfileObituaryHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(UpdateProfileObituaryCommand $command): void
    {
        $profileUuid = $this->profileRepository->getExistingProfileUuidForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileUuid === null || $profileUuid === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $profileEntity = new ProfileEntity(profileUuidValueObject: new UuidValueObject($profileUuid));

        $profileEntity->setObituary($command->obituaryValueObject);

        $this->profileRepository->updateProfileObituary($profileEntity);
    }
}
