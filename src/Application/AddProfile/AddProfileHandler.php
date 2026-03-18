<?php

declare(strict_types = 1);

namespace App\Application\AddProfile;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\User\UserEntity;
use App\Domain\Service\UuidInterface;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\Status;
use App\ValueObject\UuidValueObject;

class AddProfileHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository,
        private readonly UuidInterface $uuid
    ) {
    }

    public function __invoke(AddProfileCommand $command): void
    {
        $profileEntity = new ProfileEntity(new UuidValueObject($this->uuid->generate()));

        $userEntity = new UserEntity($command->emailValueObject);

        $profileEntity
            ->setUser($userEntity)
            ->setOrderId($command->orderIdValueObject)
            ->setProfileNameFont(new ProfileNameFontValueObject(ProfileEntity::DEFAULT_EVENT_NAME_FONT));

        $this->profileRepository->saveProfile($profileEntity);
    }
}
