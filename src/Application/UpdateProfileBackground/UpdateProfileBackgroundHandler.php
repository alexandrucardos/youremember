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
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(UpdateProfileBackgroundCommand $command): void
    {
        $profileId = $this->eventRepository->getExistingProfileIdForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileId === null || $profileId === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $eventEntity = new ProfileEntity(new ProfileIdValueObject($profileId));

        $eventEntity->setOrderId($command->orderIdValueObject)->setBackgroundFile($command->backgroundFile);

        $this->eventRepository->updateBackgroundFile($eventEntity);
    }
}
