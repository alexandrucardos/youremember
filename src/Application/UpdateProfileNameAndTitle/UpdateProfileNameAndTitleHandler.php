<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileNameAndTitle;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\ProfileIdValueObject;

class UpdateProfileNameAndTitleHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(UpdateProfileNameAndTitleCommand $command): void
    {
        $profileId = $this->profileRepository->getExistingProfileIdForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileId === null || $profileId === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $profileEntity = new ProfileEntity(profileIdValueObject: new ProfileIdValueObject($profileId));

        $profileEntity
            ->setProfileName($command->profileNameValueObject)
            ->setProfileNameFont($command->profileNameFontValueObject)
            ->setTitle($command->titleValueObject);

        $this->profileRepository->updateProfileNameTitleAndFont($profileEntity);
    }
}
