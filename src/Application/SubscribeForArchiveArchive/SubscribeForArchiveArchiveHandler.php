<?php

declare(strict_types = 1);

namespace App\Application\SubscribeForArchiveArchive;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\ValueObject\UuidValueObject;

class SubscribeForArchiveArchiveHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(SubscribeForArchiveCommand $command): void
    {
        [$eventUuid, $eventStatus] = $this->eventRepository->getExistingEventUuidAndStatus($command->uuidValueObject);

        $eventEntity = new EventEntity(
            eventUuidValueObject: new UuidValueObject($eventUuid),
            eventStatus: $eventStatus
        );

        $eventEntity->setImageArchiveEmail($command->emailValueObject);

        $this->eventRepository->saveArchiveEmailForEvent($eventEntity);
    }
}
