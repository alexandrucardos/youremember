<?php

declare(strict_types=1);

namespace App\Application\DeleteMedia;

use App\Domain\Model\Profile\EventServiceInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\MediaRepositoryInterface;
use App\Domain\Model\Profile\MediaServiceInterface;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\UuidValueObject;

final class DeleteMediaHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    )
    {
    }

    public function __invoke(DeleteMediaCommand $command): void
    {
        $uuid = $this->eventRepository->getExistingProfileUuidForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($uuid === null || $uuid === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $eventEntity = new ProfileEntity(profileUuidValueObject: new UuidValueObject($eventUuid));

        $eventEntity->addMediaPathsForDeletion(paths: $command->filePaths);

        $this->eventRepository->deleteMediaFiles($eventEntity);
    }
}
