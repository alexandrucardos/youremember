<?php

declare(strict_types = 1);

namespace App\Service\Media;

use App\Exception\Media\NotFoundException;
use App\Repository\ProfileRepository;
use App\Service\Bucket\BucketProviderInterface;

class MediaPresignService
{
    public function __construct(
        private readonly BucketProviderInterface $bucketProvider,
        private readonly ProfileRepository $eventRepository
    ) {
    }

    /**
     * @return array{key: string, uploadId: string}
     */
    public function initiateMultipartUpload(int $orderId, string $filename, string $mimeType): array
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if ($event === null) {
            throw new NotFoundException('Event not found for order: ' . $orderId);
        }

        $key = sprintf('%d/%s', $orderId, $filename);
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
