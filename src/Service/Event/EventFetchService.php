<?php

namespace App\Service\Event;

use App\Entity\Event;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;
use App\ValueObject\Event\EventFetchValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;

final class EventFetchService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    )
    {
    }

    public function fetchByOrderId(OrderIdValueObject $orderId): EventFetchValueObject
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId->value]);

        if (!$event instanceof Event) {
            throw new NotFoundException('Event not found.');
        }

        return new EventFetchValueObject(
            uuid: $event->getUuid(),
            name: $event->getName(),
            orderId: $event->getOrderId(),
            backgroundImage: $event->getBackgroundImage(),
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
            backgroundImage: $event->getBackgroundImage(),
        );
    }
}

