<?php

declare(strict_types = 1);

namespace App\Application\AddProfile;

use App\Domain\Model\Profile\Message\ProfileIdExistsException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\User\UserEntity;
use App\ValueObject\ProfileNameFontValueObject;

class AddProfileHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(AddProfileCommand $command): void
    {
        $existingProfileId = $this->profileRepository->getExistingProfileId($command->profileIdValueObject);

        if ($existingProfileId !== null) {
            throw new ProfileIdExistsException("Profile id :{$existingProfileId} already exists!");
        }

        $profileEntity = new ProfileEntity($command->profileIdValueObject);

        $userEntity = new UserEntity($command->emailValueObject);

        $profileEntity
            ->setUser($userEntity)
            ->setOrderId($command->orderIdValueObject)
            ->setProfileNameFont(new ProfileNameFontValueObject(ProfileEntity::DEFAULT_EVENT_NAME_FONT));

        $this->profileRepository->saveProfile($profileEntity);
    }
}
