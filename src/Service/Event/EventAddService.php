<?php

namespace App\Service\Event;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\ValueObject\Event\EventAddValueObject;

final class EventAddService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    )
    {
    }

    public function add(EventAddValueObject $eventAddDto): Event
    {
        return $this->eventRepository->save($eventAddDto);
    }
}