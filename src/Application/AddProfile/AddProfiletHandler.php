<?php

declare(strict_types = 1);

namespace App\Application\AddEvent;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\User\UserEntity;
use App\Domain\Service\UuidInterface;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\Status;
use App\ValueObject\UuidValueObject;

class AddProfiletHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository,
        private readonly UuidInterface $uuid
    ) {
    }

    public function __invoke(AddProfileCommand $command): void
    {
        $eventEntity = new ProfileEntity(new UuidValueObject($this->uuid->generate()), Status::VALID);

        $userEntity = new UserEntity($command->emailValueObject);

        $eventEntity
            ->setUser($userEntity)
            ->setOrderId($command->orderIdValueObject)
            ->setNeedsManualProcessing($command->needsManualProcessing)
            ->setEventStartDate($command->eventStartDateValueObject)
            ->setEventNameFont(new EventNameFontValueObject(ProfileEntity::DEFAULT_EVENT_NAME_FONT))
            ->updateStatusForOrderStatus($command->orderStatusValueObject);

        $this->eventRepository->saveEvent($eventEntity);
    }
}
