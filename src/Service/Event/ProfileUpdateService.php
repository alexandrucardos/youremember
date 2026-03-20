<?php

declare(strict_types = 1);

namespace App\Service\Event;

use App\Entity\Profile;
use App\Exception\Event\NotFoundException;
use App\Repository\ProfileRepository;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\ProfileIdValueObject;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\ProfileNameValueObject;

final class ProfileUpdateService
{
    public function __construct(
        private readonly ProfileRepository $eventRepository
    ) {
    }

    public function updateName(
        OrderIdValueObject $orderId,
        ProfileNameValueObject $name,
        ProfileNameFontValueObject $nameFont
    ): Profile {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId->value]);

        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        $event->setName($name->value)->setNameFont($nameFont->value);

        $this->eventRepository->save($event);

        return $event;
    }

    public function updateNameForEventUuid(
        ProfileIdValueObject $eventUuid,
        ProfileNameValueObject $name,
        ProfileNameFontValueObject $nameFont
    ): Profile {
        $event = $this->eventRepository->findOneBy(['uuid' => $eventUuid->value]);

        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        $event->setName($name->value)->setNameFont($nameFont->value);

        $this->eventRepository->save($event);

        return $event;
    }
}
