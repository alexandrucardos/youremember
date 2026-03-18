<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileBackground;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\ValueObject\Status;
use App\ValueObject\UuidValueObject;

class UpdateProfileBackgroundHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(UpdateProfileBackgroundCommand $command): void
    {
        $uuid = $this->eventRepository->getExistingEventUuidForOrderId($command->orderIdValueObject);

        if ($uuid === null || $uuid === '') {
            throw new ProfileNotFoundException('Event not found');
        }

        $eventEntity = new ProfileEntity(new UuidValueObject($uuid), Status::VALID);

        $eventEntity
            ->setOrderId($command->orderIdValueObject)
            ->setIsAdmin($command->userRole)
            ->setBackgroundFile($command->backgroundFile);

        $this->eventRepository->updateBackgroundFile($eventEntity);
    }
}
