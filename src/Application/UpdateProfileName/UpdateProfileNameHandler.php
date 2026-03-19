<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileName;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\UuidValueObject;

class UpdateProfileNameHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(UpdateProfileNameCommand $command): void
    {
        $profileUuid = $this->eventRepository->getExistingProfileUuidForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileUuid === null || $profileUuid === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $eventEntity = new ProfileEntity(profileUuidValueObject: new UuidValueObject($profileUuid));

        $eventEntity
            ->setProfileName($command->eventNameValueObject)
            ->setProfileNameFont($command->eventNameFontValueObject);

        $this->eventRepository->updateProfileNameAndFont($eventEntity);
    }
}
