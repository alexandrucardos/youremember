<?php

declare(strict_types = 1);

namespace App\Application\UpdateEventStatus;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\ValueObject\Status;
use App\ValueObject\UuidValueObject;

class UpdateEventStatusHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(UpdateEventStatusCommand $command): void
    {
        $uuid = $this->eventRepository->getExistingEventUuidForOrderId($command->orderIdValueObject);

        if ($uuid === null || $uuid === '') {
            throw new EventNotFoundException('Event not found');
        }

        $eventEntity = new EventEntity(new UuidValueObject($uuid), Status::VALID);

        $eventEntity->updateStatusForOrderStatus($command->orderStatusValueObject);

        $this->eventRepository->updateEventStatus($eventEntity);
    }
}
