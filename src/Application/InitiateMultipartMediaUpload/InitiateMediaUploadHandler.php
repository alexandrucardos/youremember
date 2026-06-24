<?php

declare(strict_types = 1);

namespace App\Application\InitiateMultipartMediaUpload;

use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\ProfileIdValueObject;

final class InitiateMediaUploadHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(InitiateMediaUploadCommand $command): array
    {
        $profileId = $this->profileRepository->getExistingProfileIdForOrderIdAndEmail(
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

        return $this->profileRepository->fetchMultipartInitData($profileEntity);
    }
}
