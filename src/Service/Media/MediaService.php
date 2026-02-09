<?php

namespace App\Service\Media;

use App\Entity\Media;
use App\Exception\Media\NotFoundException;
use App\Repository\EventRepository;
use App\Repository\MediaRepository;
use App\Service\Bucket\BucketProviderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaService
{
    public const FOLDER_CLIENT = 'client';
    public const FILE_BACKGROUND_NAME = 'background';

    public function __construct(
        private readonly BucketProviderInterface $bucketProvider,
        private readonly EventRepository         $eventRepository,
        private readonly MediaRepository         $mediaRepository,
        private readonly string                  $bucketName,
        private readonly string                  $region,
    )
    {
    }

    /**
     * @param array<UploadedFile> $files
     */
    public function uploadMultiple(
        int    $orderId,
        array  $files,
        string $folder = self::FOLDER_CLIENT
    ): void
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if ($event === null) {
            throw new NotFoundException('Event not found for order: ' . $orderId);
        }

        foreach ($files as $file) {
            $key = sprintf(
                '%d/%s/%s',
                $orderId,
                $folder,
                $file->getClientOriginalName()
            );

            $scaledContent = $this->scaleContentToMaxSize($file);
            $thumbnailContent = $this->createThumbnail($file);
            $thumbnailKey = $thumbnailContent !== null
                ? $this->buildThumbnailKey($key)
                : null;

            $media = (new Media())
                ->setEvent($event)
                ->setFilePath($key)
                ->setThumbnailPath($thumbnailKey)
                ->setUploaderHash($folder)
                ->setFileType($file->getMimeType())
                ->setFileSize(strlen($scaledContent))
                ->setOriginalFilename($file->getClientOriginalName());

            $this->mediaRepository->save($media);

            $this->bucketProvider->putObject(
                $key,
                $scaledContent,
                $file->getMimeType()
            );

            if ($thumbnailContent !== null && $thumbnailKey !== null) {
                $this->bucketProvider->putObject(
                    $thumbnailKey,
                    $thumbnailContent,
                    $file->getMimeType()
                );
            }
        }
    }

    private function scaleContentToMaxSize(UploadedFile $file, int $maxSizeBytes = 1048576): string
    {
        $content = file_get_contents($file->getPathname());

        if (strlen($content) <= $maxSizeBytes) {
            return $content;
        }

        $image = $this->createImageResource($file);
        if ($image === null) {
            return $content;
        }

        $mimeType = $file->getMimeType();
        $quality = 90;
        $minQuality = 10;

        while (strlen($content) > $maxSizeBytes && $quality >= $minQuality) {
            $content = $this->encodeImage($image, $mimeType, $quality);
            $quality -= 10;
        }

        imagedestroy($image);

        return $content;
    }

    private function createThumbnail(UploadedFile $file, int $maxWidth = 300): ?string
    {
        $image = $this->createImageResource($file);
        if ($image === null) {
            return null;
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        if ($originalWidth <= $maxWidth) {
            $content = $this->encodeImage($image, $file->getMimeType(), 80);
            imagedestroy($image);
            return $content;
        }

        $newWidth = $maxWidth;
        $newHeight = (int)floor($originalHeight * ($maxWidth / $originalWidth));

        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        if ($file->getMimeType() === 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        $thumbnailContent = $this->encodeImage($thumbnail, $file->getMimeType(), 80);

        imagedestroy($image);
        imagedestroy($thumbnail);

        return $thumbnailContent;
    }

    private function buildThumbnailKey(string $key): string
    {
        $pathInfo = pathinfo($key);
        $directory = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? '';
        $extension = $pathInfo['extension'] ?? '';

        return sprintf(
            '%s/%s%s.%s',
            $directory,
            $filename,
            MediaRepository::THUMBNAIL_SUFFIX,
            $extension
        );
    }

    private function createImageResource(UploadedFile $file): ?\GdImage
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'video/')) {
            return null;
        }

        if (!str_starts_with($mimeType, 'image/') || !extension_loaded('gd')) {
            return null;
        }

        $content = file_get_contents($file->getPathname());
        $image = @imagecreatefromstring($content);

        return $image === false ? null : $image;
    }

    private function encodeImage(\GdImage $image, string $mimeType, int $quality): string
    {
        ob_start();

        match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, null, $quality),
            'image/png' => imagepng($image, null, (int)floor((100 - $quality) / 10)),
            'image/webp' => imagewebp($image, null, $quality),
            default => imagejpeg($image, null, $quality),
        };

        return ob_get_clean();
    }

    public function uploadBackground(int $orderId, UploadedFile $file): void
    {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if ($event === null) {
            throw new NotFoundException('Event not found for order: ' . $orderId);
        }

        $key = sprintf(
            '%d/%s/%s',
            $orderId,
            self::FOLDER_CLIENT,
            self::FILE_BACKGROUND_NAME
        );

        $scaledContent = $this->scaleContentToMaxSize($file);

        $mediaBackground = $this->mediaRepository->findOneBy(['file_path' => $key]);

        if ($mediaBackground === null) {
            $mediaBackground = (new Media())
                ->setEvent($event)
                ->setFilePath($key)
                ->setUploaderHash(self::FOLDER_CLIENT)
                ->setFileType($file->getMimeType())
                ->setFileSize(strlen($scaledContent))
                ->setOriginalFilename($file->getClientOriginalName());
        }

        $this->mediaRepository->save($mediaBackground);

        $this->bucketProvider->putObject(
            $key,
            $scaledContent,
            $file->getMimeType()
        );
    }

    public function buildUrl(string $prefix): string
    {
        return sprintf(
            'https://%s.s3.%s.amazonaws.com/%s',
            $this->bucketName,
            $this->region,
            $prefix
        );
    }
}