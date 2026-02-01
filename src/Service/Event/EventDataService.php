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
        $eventUuid = $eventFetchDto->uuid;

        $backgroundPictureUrl = $eventFetchDto->backgroundImage;

        if (null === $backgroundPictureUrl) {
            $backgroundPictureUrl = $this->mediatorS3Service->buildUrl(
                sprintf(
                    '%d/%s/%s',
                    $eventUuid,
                    MediatorS3Service::FOLDER_PROFILE,
                    MediatorS3Service::PROFILE_BACKGROUND
                )
            );
        }

        $picturesUrls = $this->mediatorS3Service->fetchContentUrls(
            sprintf(
                '%d/%s',
                $eventUuid,
                MediatorS3Service::FOLDER_CLIENT,
            )
        );

        return new EventDataValueObject(
            backgroundPictureUrl: $backgroundPictureUrl,
            pictures: $picturesUrls
        );
    }
}

