<?php

declare(strict_types = 1);

namespace App\Service\Media;

use App\Entity\Media;
use App\Exception\Media\NotFoundException;
use App\Repository\MediaRepository;
use App\Repository\ProfileRepository;
use App\Service\Bucket\BucketProviderInterface;

class MediaConfirmService
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly ProfileRepository $eventRepository,
        private readonly BucketProviderInterface $bucketProvider
    ) {
    }

    /**
     * @param array<array{PartNumber: int, ETag: string}> $parts
     */
    public function completeMultipartUpload(
        int $orderId,
        string $key,
        string $uploadId,
        string $filename,
        string $mimeType,
        int $fileSize,
        array $parts
    ): void {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if ($event === null) {
            throw new NotFoundException('Event not found for order: ' . $orderId);
        }

        $this->bucketProvider->completeMultipartUpload($key, $uploadId, $parts);

        $media = (new Media())
            ->setProfile($event)
            ->setFilePath($key)
            ->setThumbnailPath(null)
            ->setFileType($mimeType)
            ->setFileSize($fileSize)
            ->setOriginalFilename($filename);

        $this->mediaRepository->save($media);
    }
}
