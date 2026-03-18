<?php

declare(strict_types = 1);

namespace App\Application\DeleteMedia;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\EventServiceInterface;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\Domain\Model\Event\MediaRepositoryInterface;
use App\Domain\Model\Event\MediaServiceInterface;
use App\Domain\Model\Event\Message\EventNotValidException;
use App\ValueObject\Status;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;

final class DeleteMediaHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(DeleteMediaCommand $command): void
    {
        [$eventUuid, $eventStatus] =
            $this->eventRepository->getExistingEventUuidAndStatus($command->eventUuidValueObject);

        if ($eventUuid === null || $eventUuid === '') {
            throw new EventNotFoundException('Event not found');
        }

        $eventEntity = new EventEntity(
            eventUuidValueObject: new UuidValueObject($eventUuid),
            eventStatus: $eventStatus
        );

        $eventEntity
            ->setIsAdmin($command->userRole)
            ->setMediaUserIdentifier($command->userIdentifier)
            ->addMediaPathsForDeletion(paths: $command->filePaths);

        $this->eventRepository->deleteMediaFiles($eventEntity);
    }
}
