<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileName;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\ProfileIdValueObject;

class UpdateProfileNameHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(UpdateProfileNameCommand $command): void
    {
        $profileId = $this->eventRepository->getExistingProfileIdForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileId === null || $profileId === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $profileEntity = new ProfileEntity(profileIdValueObject: new ProfileIdValueObject($profileId));

        $profileEntity
            ->setProfileName($command->eventNameValueObject)
            ->setProfileNameFont($command->eventNameFontValueObject);

        $this->eventRepository->updateProfileNameAndFont($profileEntity);
    }
}
