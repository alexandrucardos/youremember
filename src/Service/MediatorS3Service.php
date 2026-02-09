<?php

namespace App\Service;

use App\Entity\Media;
use App\Exception\Media\NotFoundException;
use App\Exception\Media\UnauthorizedException;
use App\Repository\EventRepository;
use App\Repository\MediaRepository;
use App\Service\Bucket\BucketProviderInterface;
use App\ValueObject\HashValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediatorS3Service
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
        $mimeType = $file->getMimeType();

        if (strlen($content) <= $maxSizeBytes) {
            return $content;
        }

        if (str_starts_with($mimeType, 'video/')) {
            return $content;
        }

        if (!str_starts_with($mimeType, 'image/') || !extension_loaded('gd')) {
            return $content;
        }

        $image = @imagecreatefromstring($content);
        if ($image === false) {
            return $content;
        }

        $quality = 90;
        $minQuality = 10;

        while (strlen($content) > $maxSizeBytes && $quality >= $minQuality) {
            ob_start();

            match ($mimeType) {
                'image/jpeg', 'image/jpg' => imagejpeg($image, null, $quality),
                'image/png' => imagepng($image, null, (int)floor((100 - $quality) / 10)),
                'image/webp' => imagewebp($image, null, $quality),
                default => imagejpeg($image, null, $quality),
            };

            $content = ob_get_clean();
            $quality -= 10;
        }

        imagedestroy($image);

        return $content;
    }

    private function createThumbnail(UploadedFile $file, int $maxWidth = 300): ?string
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

        if ($image === false) {
            return null;
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        if ($originalWidth <= $maxWidth) {
            imagedestroy($image);
            return $content;
        }

        $newWidth = $maxWidth;
        $newHeight = (int)floor($originalHeight * ($maxWidth / $originalWidth));

        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        if ($mimeType === 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        ob_start();

        match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($thumbnail, null, 80),
            'image/png' => imagepng($thumbnail, null, 6),
            'image/webp' => imagewebp($thumbnail, null, 80),
            default => imagejpeg($thumbnail, null, 80),
        };

        $thumbnailContent = ob_get_clean();

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

    public function deleteContent(string $url, HashValueObject $hash): void
    {
        $key = $this->getS3KeyFromUrl($url);
        $folder = $this->extractFolderFromKey($key);

        if ($folder !== $hash->value) {
            throw new UnauthorizedException('Not authorized to delete this media');
        }

        $this->deleteByKey($key);
    }

    private function getS3KeyFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['host'], $parsed['path'])) {
            throw new \InvalidArgumentException('Invalid URL');
        }

        return urldecode(ltrim($parsed['path'], '/'));
    }

    private function extractFolderFromKey(string $key): string
    {
        $parts = explode('/', $key);

        if (count($parts) < 2) {
            throw new \InvalidArgumentException('Invalid key format');
        }

        return $parts[1];
    }

    private function deleteByKey(string $key): void
    {
        $this->mediaRepository->softDeleteByPath($key);
    }

    public function deleteByUrl(string $url): void
    {
        //todo make it by batch delete
        $key = $this->getS3KeyFromUrl($url);

        $this->deleteByKey($key);
    }
}