<?php

namespace App\Application\AddFeedback;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;

class AddFeedbackHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
    )
    {
    }

    public function __invoke(AddFeedbackCommand $command): void
    {
        $eventEntity = new EventEntity(
            $command->uuidValueObject,
        );

        $eventEntity->setFeedbackValueObject($command->feedbackValueObject);

        $this->eventRepository->saveFeedbackForEvent($eventEntity);
    }
}