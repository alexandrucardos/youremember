<?php

namespace App\Service\Event;

use App\DTO\Event\EventFetchDto;
use App\Entity\Event;
use App\Exception\Event\InvalidOrderIdException;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;

final class EventFetchService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    )
    {
    }

    public function fetchByOrderId(mixed $idRaw): EventFetchDto
    {
        //todo this should be a validator
        $id = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new InvalidOrderIdException(
                'Invalid order id.');
        }

        $event = $this->eventRepository->find($id);

        if (!$event instanceof Event) {
            throw new NotFoundException('Event not found.');
        }

        //todo add backgroundimage if user sets one

        return new EventFetchDto(
            uuid: $event->getUuid(),
            name: $event->getName(),
            orderId: $event->getOrderId(),
            backgroundImage: null
        );
    }
}

