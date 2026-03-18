<?php

declare(strict_types = 1);

namespace App\Application\AddFeedback;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\ValueObject\UuidValueObject;

class AddFeedbackHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(AddFeedbackCommand $command): void
    {
        [$uuid, $status] = $this->eventRepository->getExistingEventUuidAndStatus($command->uuidValueObject);

        $eventEntity = new EventEntity(new UuidValueObject($uuid), $status);

        $eventEntity->setFeedbackValueObject($command->feedbackValueObject);

        $this->eventRepository->saveFeedbackForEvent($eventEntity);
    }
}
