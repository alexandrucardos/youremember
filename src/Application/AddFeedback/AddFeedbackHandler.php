<?php

declare(strict_types = 1);

namespace App\Application\AddFeedback;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\UuidValueObject;

class AddFeedbackHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(AddFeedbackCommand $command): void
    {
        [$uuid, $status] = $this->eventRepository->getExistingEventUuidAndStatus($command->uuidValueObject);

        $eventEntity = new ProfileEntity(new UuidValueObject($uuid), $status);

        $eventEntity->setFeedbackValueObject($command->feedbackValueObject);

        $this->eventRepository->saveFeedbackForEvent($eventEntity);
    }
}
