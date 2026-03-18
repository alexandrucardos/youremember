<?php

declare(strict_types = 1);

namespace App\Application\DeleteMedia;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\EventServiceInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\MediaRepositoryInterface;
use App\Domain\Model\Profile\MediaServiceInterface;
use App\Domain\Model\Profile\Message\ProfileNotValidException;
use App\ValueObject\Status;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;

final class DeleteMediaHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(DeleteMediaCommand $command): void
    {
        [$eventUuid, $eventStatus] =
            $this->eventRepository->getExistingEventUuidAndStatus($command->eventUuidValueObject);

        if ($eventUuid === null || $eventUuid === '') {
            throw new ProfileNotFoundException('Event not found');
        }

        $eventEntity = new ProfileEntity(
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
