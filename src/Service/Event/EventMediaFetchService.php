<?php

declare(strict_types = 1);

namespace App\Service\Event;

use App\Domain\Model\Profile\ProfileEntity;
use App\Repository\MediaRepository;
use App\Service\Media\MediaService;
use App\ValueObject\OrderIdValueObject;

class EventMediaFetchService
{
    public function __construct(
        private readonly MediaService $mediatorS3Service,
        private readonly MediaRepository $mediaRepository
    ) {
    }

    public function fetch(array $eventFetchData): array
    {
        $orderId = $eventFetchData['orderId'];

        $backgroundPictureUrl = $eventFetchData['backgroundImage'];

        if (null === $backgroundPictureUrl) {
            $backgroundPictureUrl = $this->mediatorS3Service->buildUrl(sprintf(
                '%d/%s/%s',
                $orderId,
                MediaService::FOLDER_CLIENT,
                MediaService::FILE_BACKGROUND_NAME
            ));
        }

        $urls = $this->fetchContentUrlsByOrderId($orderId);

        return [
            'backgroundPictureUrl' => $backgroundPictureUrl,
            'pictures' => array_filter(
                $urls,
                static fn(string $url) => !str_ends_with($url, '/' . MediaService::FILE_BACKGROUND_NAME)
            )
        ];
    }

    public function fetchForOrderId(OrderIdValueObject $orderIdValueObject): array
    {
        $urls = $this->fetchContentUrlsByOrderId($orderIdValueObject->value);

        $backgroundPictureUrl = $this->mediatorS3Service->buildUrl(sprintf(
            '%d/%s/%s',
            $orderIdValueObject->value,
            ProfileEntity::ADMIN_USER_IDENTIFIER,
            MediaService::FILE_BACKGROUND_NAME
        ));

        return [
            'backgroundPictureUrl' => $backgroundPictureUrl,
            'pictures' => array_filter(
                $urls,
                static fn(string $url) => !str_ends_with($url, '/' . MediaService::FILE_BACKGROUND_NAME)
            )
        ];
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
