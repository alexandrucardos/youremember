<?php

declare(strict_types = 1);

namespace App\Application\AddEvent;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\User\UserEntity;
use App\Domain\Service\UuidInterface;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\Status;
use App\ValueObject\UuidValueObject;

class AddEventHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly UuidInterface $uuid
    ) {
    }

    public function __invoke(AddEventCommand $command): void
    {
        $eventEntity = new EventEntity(new UuidValueObject($this->uuid->generate()), Status::VALID);

        $userEntity = new UserEntity($command->emailValueObject);

        $eventEntity
            ->setUser($userEntity)
            ->setOrderId($command->orderIdValueObject)
            ->setNeedsManualProcessing($command->needsManualProcessing)
            ->setEventStartDate($command->eventStartDateValueObject)
            ->setEventNameFont(new EventNameFontValueObject(EventEntity::DEFAULT_EVENT_NAME_FONT))
            ->updateStatusForOrderStatus($command->orderStatusValueObject);

        $this->eventRepository->saveEvent($eventEntity);
    }
}
