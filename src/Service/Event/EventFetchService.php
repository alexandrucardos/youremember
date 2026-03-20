<?php

declare(strict_types = 1);

namespace App\Service\Event;

use App\Entity\Profile;
use App\Exception\Event\NotFoundException;
use App\Repository\ProfileRepository;
use App\Service\Media\MediaService;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\ProfileIdValueObject;

final class EventFetchService
{
    public function __construct(
        private readonly ProfileRepository $eventRepository,
        private readonly MediaService $mediatorS3Service
    ) {
    }

    public function fetchByOrderId(OrderIdValueObject $orderId): array
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

        return [
            'uuid' => $event->getExternalId(),
            'name' => $event->getName(),
            'nameFont' => $event->getNameFont(),
            'orderId' => $event->getOrderId(),
            'backgroundImage' => $backgroundPictureUrl
        ];
    }

    public function fetchByUuid(ProfileIdValueObject $uuid): array
    {
        $event = $this->eventRepository->findOneBy(['external_id' => $uuid->value]);

        if (!$event instanceof Profile) {
            throw new NotFoundException('Event not found.');
        }

        return [
            'uuid' => $event->getUuid(),
            'name' => $event->getName(),
            'nameFont' => $event->getNameFont(),
            'orderId' => $event->getOrderId(),
            'backgroundImage' => null
        ];
    }
}
