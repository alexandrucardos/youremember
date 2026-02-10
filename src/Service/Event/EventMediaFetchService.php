<?php

namespace App\Service\Event;

use App\Repository\MediaRepository;
use App\Service\Media\MediaService;
use App\ValueObject\Event\EventDataValueObject;
use App\ValueObject\Event\EventFetchValueObject;

class EventMediaFetchService
{
    public function __construct(
        private readonly MediaService    $mediatorS3Service,
        private readonly MediaRepository $mediaRepository,
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
                    MediaService::FOLDER_CLIENT,
                    MediaService::FILE_BACKGROUND_NAME
                )
            );
        }

        $urls = $this->fetchContentUrlsByOrderId($orderId);

        return new EventDataValueObject(
            backgroundPictureUrl: $backgroundPictureUrl,
            pictures: array_filter(
                $urls,
                fn(string $url) => !str_ends_with($url, '/' . MediaService::FILE_BACKGROUND_NAME)
            )
        );
    }

    public function fetchContentUrlsByOrderId(int $orderId): array
    {
        $paths = $this->mediaRepository->findPathsByOrderId($orderId);

        $urls = [];

        foreach ($paths as $path) {
            $urls[] = $this->mediatorS3Service->buildUrl(reset($path));
        }

        return $urls;
    }
}

