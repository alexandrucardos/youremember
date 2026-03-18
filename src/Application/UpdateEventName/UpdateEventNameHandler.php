<?php

declare(strict_types = 1);

namespace App\Application\UpdateEventName;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\ValueObject\UuidValueObject;

class UpdateEventNameHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(UpdateEventNameCommand $command): void
    {
        [$eventUuid, $eventStatus] =
            $this->eventRepository->getExistingEventUuidAndStatus($command->eventUuidValueObject);

        $eventEntity = new EventEntity(
            eventUuidValueObject: new UuidValueObject($eventUuid),
            eventStatus: $eventStatus
        );

        $eventEntity
            ->setEventName($command->eventNameValueObject)
            ->setEventNameFont($command->eventNameFontValueObject);

        $this->eventRepository->updateEventNameAndFont($eventEntity);
    }
}
