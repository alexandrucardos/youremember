<?php

declare(strict_types = 1);

namespace App\Application\AddProfile;

use App\Domain\Model\Profile\Message\OrderAlreadyAssociatedException;
use App\Domain\Model\Profile\Message\ProfileIdExistsException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\User\UserEntity;
use App\Domain\ValueObject\ProfileNameFontValueObject;

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

        $existingProfileId = $this->profileRepository->getExistingProfileIdForOrderId($command->orderIdValueObject);

        if ($existingProfileId !== null) {
            throw new OrderAlreadyAssociatedException(sprintf(
                'Order id :%s, associated to profileId :%d',
                $command->orderIdValueObject->value,
                $existingProfileId
            ));
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
