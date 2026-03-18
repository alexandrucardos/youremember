<?php

declare(strict_types = 1);

namespace App\Application\UpdateEventName;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\ValueObject\UuidValueObject;

class UpdateProfileNameHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(UpdateProfileNameCommand $command): void
    {
        [$eventUuid, $eventStatus] =
            $this->eventRepository->getExistingEventUuidAndStatus($command->eventUuidValueObject);

        $eventEntity = new ProfileEntity(
            eventUuidValueObject: new UuidValueObject($eventUuid),
            eventStatus: $eventStatus
        );

        $eventEntity
            ->setEventName($command->eventNameValueObject)
            ->setEventNameFont($command->eventNameFontValueObject);

        $this->eventRepository->updateEventNameAndFont($eventEntity);
    }
}
