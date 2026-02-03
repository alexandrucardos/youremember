<?php

namespace App\Service\Event;

use App\Service\MediatorS3Service;
use App\ValueObject\Event\EventDataValueObject;
use App\ValueObject\Event\EventFetchValueObject;

class EventDataService
{
    public function __construct(
        private readonly MediatorS3Service $mediatorS3Service,
    )
    {
    }

    public function fetch(EventFetchValueObject $eventFetchDto): EventDataValueObject
    {
        $orderId = $eventFetchDto->orderId;

        $backgroundPictureUrl = $eventFetchDto->backgroundImage;

        if (null === $backgroundPictureUrl) {
            $backgroundPictureUrl = $this->mediatorS3Service->buildUrl(
                sprintf(
                    '%d/%s/%s',
                    $orderId,
                    MediatorS3Service::FOLDER_CLIENT,
                    MediatorS3Service::FILE_BACKGROUND_NAME
                )
            );
        }

        $urls = $this->mediatorS3Service->fetchContentUrls(
            sprintf(
                '%d',
                $orderId,
            )
        );

        return new EventDataValueObject(
            backgroundPictureUrl: $backgroundPictureUrl,
            pictures: array_filter(
                $urls,
                fn(string $url) => !str_ends_with($url, '/' . MediatorS3Service::FILE_BACKGROUND_NAME)
            )
        );
    }
}

