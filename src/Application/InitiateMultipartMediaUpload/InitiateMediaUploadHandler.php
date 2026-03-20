<?php

declare(strict_types = 1);

namespace App\Application\InitiateMultipartMediaUpload;

use App\Domain\Model\Profile\EventServiceInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\MediaRepositoryInterface;
use App\Domain\Model\Profile\MediaServiceInterface;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\ProfileIdValueObject;

final class InitiateMediaUploadHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(InitiateMediaUploadCommand $command): array
    {
        $profileId = $this->eventRepository->getExistingProfileIdForOrderIdAndEmail(
            $command->orderIdValueObject,
            $command->userEmail
        );

        if ($profileId === null || $profileId === '') {
            throw new ProfileNotFoundException('Profile not found');
        }

        $profileEntity = new ProfileEntity(profileIdValueObject: new ProfileIdValueObject($profileId));

        $profileEntity
            ->setMultipartFilename($command->filename)
            ->setMultipartMimeType($command->mimeType)
            ->setOrderId($command->orderIdValueObject);

        return $this->eventRepository->fetchMultipartInitData($profileEntity);
    }
}
