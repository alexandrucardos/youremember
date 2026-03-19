<?php

declare(strict_types=1);

namespace App\Application\InitiateMultipartMediaUpload;

use App\Domain\Model\Profile\EventServiceInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\MediaRepositoryInterface;
use App\Domain\Model\Profile\MediaServiceInterface;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\UuidValueObject;

final class InitiateMediaUploadHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    )
    {
    }

    public function __invoke(InitiateMediaUploadCommand $command): array
    {
        $uuid = $this->eventRepository->getExistingProfileUuidForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($uuid === null || $uuid === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $profileEntity = new ProfileEntity(profileUuidValueObject: new UuidValueObject($uuid));

        $profileEntity
            ->setMultipartFilename($command->filename)
            ->setMultipartMimeType($command->mimeType)
            ->setOrderId($command->orderIdValueObject);

        return $this->eventRepository->fetchMultipartInitData($profileEntity);
    }
}
