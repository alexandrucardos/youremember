<?php

namespace App\Application\AddImageArchiveEmail;

use App\Domain\Model\ImageArchive\ImageArchiveEntity;
use App\Domain\Model\ImageArchive\ImageArchiveRepositoryInterface;

class AddImageArchiveEmailHandler
{
    public function __construct(
        private readonly ImageArchiveRepositoryInterface $imageArchiveRepository,
    )
    {

    }

    public function __invoke(AddImageArchiveEmailCommand $command): void
    {
        $imageArchiveEntity = new ImageArchiveEntity(
            eventUuidValueObject: $command->uuidValueObject,
            emailValueObject: $command->emailValueObject,
        );

        $this->imageArchiveRepository->save($imageArchiveEntity);
    }
}