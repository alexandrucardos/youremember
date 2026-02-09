<?php

namespace App\Service\Event;

use App\Entity\Event;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;
use App\Service\MediatorS3Service;
use App\ValueObject\Event\EventFetchValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;

final class EventFetchService
{
    public function __construct(
        private readonly EventRepository   $eventRepository,
        private readonly MediatorS3Service $mediatorS3Service,
    )
    {
    }

    public function fetchByOrderId(OrderIdValueObject $orderId): EventFetchValueObject
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId->value]);

        if (!$event instanceof Event) {
            throw new NotFoundException('Event not found.');
        }

        $backgroundPictureUrl = $this->mediatorS3Service->buildUrl(
            sprintf(
                '%d/%s/%s',
                $orderId->value,
                MediatorS3Service::FOLDER_CLIENT,
                MediatorS3Service::FILE_BACKGROUND_NAME
            )
        );

        return new EventFetchValueObject(
            uuid: $event->getUuid(),
            name: $event->getName(),
            orderId: $event->getOrderId(),
            backgroundImage: $backgroundPictureUrl,
        );
    }

    public function fetchByUuid(UuidValueObject $uuid): EventFetchValueObject
    {
        $event = $this->eventRepository->findOneBy(['uuid' => $uuid->value]);

        if (!$event instanceof Event) {
            throw new NotFoundException('Event not found.');
        }

        return new EventFetchValueObject(
            uuid: $event->getUuid(),
            name: $event->getName(),
            orderId: $event->getOrderId(),
            backgroundImage: null,
        );
    }
}

