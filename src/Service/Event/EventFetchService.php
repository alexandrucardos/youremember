<?php

declare(strict_types = 1);

namespace App\Service\Event;

use App\Entity\Profile;
use App\Exception\Event\NotFoundException;
use App\Repository\ProfileRepository;
use App\Service\Media\MediaService;
use App\ValueObject\Event\EventFetchValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;

final class EventFetchService
{
    public function __construct(
        private readonly ProfileRepository $eventRepository,
        private readonly MediaService $mediatorS3Service
    ) {
    }

    public function fetchByOrderId(OrderIdValueObject $orderId): EventFetchValueObject
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId->value]);

        if (!$event instanceof Profile) {
            throw new NotFoundException('Event not found.');
        }

        $backgroundPictureUrl = $this->mediatorS3Service->buildUrl(sprintf(
            '%d/%s/%s',
            $orderId->value,
            MediaService::FOLDER_CLIENT,
            MediaService::FILE_BACKGROUND_NAME
        ));

        return new EventFetchValueObject(
            uuid: $event->getUuid(),
            name: $event->getName(),
            nameFont: $event->getNameFont(),
            orderId: $event->getOrderId(),
            backgroundImage: $backgroundPictureUrl
        );
    }

    public function fetchByUuid(UuidValueObject $uuid): EventFetchValueObject
    {
        $event = $this->eventRepository->findOneBy(['uuid' => $uuid->value]);

        if (!$event instanceof Profile) {
            throw new NotFoundException('Event not found.');
        }

        return new EventFetchValueObject(
            uuid: $event->getUuid(),
            name: $event->getName(),
            nameFont: $event->getNameFont(),
            orderId: $event->getOrderId(),
            backgroundImage: null
        );
    }
}
