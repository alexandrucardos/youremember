<?php

namespace App\Application\AddImageArchiveEmail;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;

class AddImageArchiveEmailHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
    )
    {
    }

    public function __invoke(AddImageArchiveEmailCommand $command): void
    {
        $eventEntity = new EventEntity(
            eventUuidValueObject: $command->uuidValueObject,
        );

        $eventEntity->setImageArchiveEmail($command->emailValueObject);

        $this->eventRepository->saveArchiveEmailForEvent($eventEntity);
    }
}