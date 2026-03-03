<?php

namespace App\Service\Media;

use App\Exception\Media\NotFoundException;
use App\Repository\EventRepository;
use App\Service\Bucket\BucketProviderInterface;

class MediaPresignService
{
    public function __construct(
        private readonly BucketProviderInterface $bucketProvider,
        private readonly EventRepository         $eventRepository,
    )
    {
    }

    /**
     * @return array{presignedUrl: string, key: string}
     */
    public function generateForOrder(int $orderId, string $filename, string $mimeType, string $folder): array
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if ($event === null) {
            throw new NotFoundException('Event not found for order: ' . $orderId);
        }

        $key = sprintf('%d/%s/%s', $orderId, $folder, $filename);

        $presignedUrl = $this->bucketProvider->getPresignedUrl(
            $key,
            $mimeType,
            new \DateTimeImmutable('+15 minutes')
        );

        return ['presignedUrl' => $presignedUrl, 'key' => $key];
    }

    /**
     * @return array{key: string, uploadId: string}
     */
    public function initiateMultipartUpload(int $orderId, string $filename, string $mimeType, string $folder): array
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if ($event === null) {
            throw new NotFoundException('Event not found for order: ' . $orderId);
        }

        $key      = sprintf('%d/%s/%s', $orderId, $folder, $filename);
        $uploadId = $this->bucketProvider->createMultipartUpload($key, $mimeType);

        return ['key' => $key, 'uploadId' => $uploadId];
    }

    public function getPresignedPartUrl(string $key, string $uploadId, int $partNumber): string
    {
        return $this->bucketProvider->getPresignedUrlForPart(
            $key,
            $uploadId,
            $partNumber,
            new \DateTimeImmutable('+15 minutes')
        );
    }

    public function abortMultipartUpload(string $key, string $uploadId): void
    {
        $this->bucketProvider->abortMultipartUpload($key, $uploadId);
    }
}