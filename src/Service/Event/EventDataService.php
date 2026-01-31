<?php

namespace App\Service\Event;

use App\DTO\Event\EventDataDto;
use App\DTO\Event\EventFetchDto;
use App\Service\MediatorS3Service;

class EventDataService
{
    public function __construct(
        private readonly MediatorS3Service $mediatorS3Service,
    )
    {
    }

    public function fetch(EventFetchDto $eventFetchDto): EventDataDto
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
                MediatorS3Service::FOLDER_PICTURES,
            )
        );

        return new EventDataDto(
            backgroundPictureUrl: $backgroundPictureUrl,
            pictures: $picturesUrls
        );
    }
}

