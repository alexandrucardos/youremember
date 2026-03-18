<?php

declare(strict_types = 1);

namespace App\Application\InitiateMultipartMediaUpload;

use App\Application\DeleteMedia\DeleteMediaCommand;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\EventServiceInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\MediaRepositoryInterface;
use App\Domain\Model\Profile\MediaServiceInterface;
use App\Domain\Model\Profile\Message\ProfileNotValidException;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\Status;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;

final class InitiateMediaUploadHandler
{
    public function __construct(
        private readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(InitiateMediaUploadCommand $command): array
    {
        [$uuid, $status] = $this->eventRepository->getExistingEventUuidAndStatus($command->eventUuidValueObject);

        $eventEntity = new ProfileEntity(eventUuidValueObject: new UuidValueObject($uuid), eventStatus: $status);

        $orderId = $this->eventRepository->fetchOrderIdForUuid($command->eventUuidValueObject->value);

        $eventEntity
            ->setIsAdmin($command->userRole)
            ->setMediaUserIdentifier($command->userIdentifier)
            ->setMultipartFilename($command->filename)
            ->setMultipartMimeType($command->mimeType)
            ->setOrderId(new OrderIdValueObject($orderId));

        return $this->eventRepository->fetchMultipartInitData($eventEntity);
    }
}
