<?php

declare(strict_types = 1);

namespace App\Service\Event;

use App\Entity\Event;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;

final class EventUpdateService
{
    public function __construct(
        private readonly EventRepository $eventRepository
    ) {
    }

    public function updateName(
        OrderIdValueObject $orderId,
        EventNameValueObject $name,
        EventNameFontValueObject $nameFont
    ): Event {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId->value]);

        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        $event->setName($name->value)->setNameFont($nameFont->value);

        $this->eventRepository->save($event);

        return $event;
    }

    public function updateNameForEventUuid(
        UuidValueObject $eventUuid,
        EventNameValueObject $name,
        EventNameFontValueObject $nameFont
    ): Event {
        $event = $this->eventRepository->findOneBy(['uuid' => $eventUuid->value]);

        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        $event->setName($name->value)->setNameFont($nameFont->value);

        $this->eventRepository->save($event);

        return $event;
    }
}
