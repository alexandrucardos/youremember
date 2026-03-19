<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfilePicture;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\UuidValueObject;

class UpdateProfilePictureHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(UpdateProfilePictureCommand $command): void
    {
        $uuid = $this->eventRepository->getExistingProfileUuidForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($uuid === null || $uuid === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $eventEntity = new ProfileEntity(new UuidValueObject($uuid));

        $eventEntity->setOrderId($command->orderIdValueObject)->setProfilePictureFile($command->profilePictureFile);

        $this->eventRepository->updateProfilePictureFile($eventEntity);
    }
}
