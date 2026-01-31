<?php

namespace App\Service\Event;

use App\DTO\Event\EventFetchDto;
use App\Entity\Event;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;
use App\ValueObject\OrderIdValueObject;

final class EventFetchService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    )
    {
    }

    public function fetchByOrderId(OrderIdValueObject $orderId): EventFetchDto
    {
        $event = $this->eventRepository->find($orderId->value);

        if (!$event instanceof Event) {
            throw new NotFoundException('Event not found.');
        }

        return new EventFetchDto(
            uuid: $event->getUuid(),
            name: $event->getName(),
            orderId: $event->getOrderId(),
            backgroundImage: $event->getBackgroundImage(),
        );
    }
}

