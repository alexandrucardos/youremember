<?php

declare(strict_types = 1);

namespace App\Application\InitiateMultipartMediaUpload;

use App\Application\DeleteMedia\DeleteMediaCommand;
use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\EventServiceInterface;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\Domain\Model\Event\MediaRepositoryInterface;
use App\Domain\Model\Event\MediaServiceInterface;
use App\Domain\Model\Event\Message\EventNotValidException;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\Status;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;

final class InitiateMediaUploadHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(InitiateMediaUploadCommand $command): array
    {
        [$uuid, $status] = $this->eventRepository->getExistingEventUuidAndStatus($command->eventUuidValueObject);

        $eventEntity = new EventEntity(eventUuidValueObject: new UuidValueObject($uuid), eventStatus: $status);

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
