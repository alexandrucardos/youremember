<?php

declare(strict_types = 1);

namespace App\Service\Media;

use App\Domain\Model\Profile\MediaServiceInterface;
use App\Entity\Media;
use App\Exception\Media\NotFoundException;
use App\Repository\MediaRepository;
use App\Repository\ProfileRepository;
use App\Service\Bucket\BucketProviderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class MediaService
{
    public const FOLDER_CLIENT = 'client';
    public const FILE_BACKGROUND_NAME = 'background';
    public const FILE_PROFILE_PICTURE_NAME = 'profile-picture';

    public const APPLE_EXTENSIONS = ['image/heic', 'image/heif'];

    public const JPEG_EXTENSION = 'jpeg';

    public function __construct(
        private readonly BucketProviderInterface $bucketProvider,
        private readonly ProfileRepository $eventRepository,
        private readonly MediaRepository $mediaRepository,
        private readonly string $bucketName,
        private readonly string $region
    ) {
    }

    /**
     * @param array<UploadedFile> $files
     */
    public function uploadMultiple(
        int $orderId,
        array $files
    ): void {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if ($event === null) {
            throw new NotFoundException('Event not found for order: ' . $orderId);
        }
        foreach ($files as $file) {
            $processedContent = $this->processContent($file);
            $thumbnailContent = $this->createThumbnail($file);

            $key = $this->buildKey($orderId, $file->getClientOriginalExtension());

            if (in_array($file->getMimeType(), self::APPLE_EXTENSIONS)) {
                $key = $this->buildKey($orderId, self::JPEG_EXTENSION);
            }

            $thumbnailKey = $thumbnailContent !== null ? $this->buildThumbnailKey($key) : null;

            $media = (new Media())
                ->setEvent($event)
                ->setFilePath($key)
                ->setThumbnailPath($thumbnailKey)
                ->setFileType($file->getMimeType())
                ->setFileSize(strlen($processedContent))
                ->setOriginalFilename($file->getClientOriginalName());

            $this->mediaRepository->save($media);

            $this->bucketProvider->putObject($key, $processedContent, $file->getMimeType());

            if ($thumbnailContent !== null && $thumbnailKey !== null) {
                $this->bucketProvider->putObject($thumbnailKey, $thumbnailContent, $file->getMimeType());
            }
        }
    }

    public function uploadProfileFile(
        int $orderId,
        UploadedFile $file,
        string $fileName
    ): void {
        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if ($event === null) {
            throw new NotFoundException('Event not found for order: ' . $orderId);
        }

        $key = sprintf('%d/%s', $orderId, $fileName);

        $scaledContent = $this->processContent($file);

        $mediaBackground = $this->mediaRepository->findOneBy(['file_path' => $key]);

        if ($mediaBackground === null) {
            $mediaBackground = (new Media())
                ->setEvent($event)
                ->setFilePath($key)
                ->setFileType($file->getMimeType())
                ->setFileSize(strlen($scaledContent))
                ->setOriginalFilename($file->getClientOriginalName());
        } else {
            $mediaBackground
                ->setFilePath($key)
                ->setFileType($file->getMimeType())
                ->setFileSize(strlen($scaledContent))
                ->setOriginalFilename($file->getClientOriginalName());
        }

        $this->mediaRepository->save($mediaBackground);

        $this->bucketProvider->putObject($key, $scaledContent, $file->getMimeType());
    }

    public function buildUrl(string $prefix): string
    {
        return sprintf('https://%s.s3.%s.amazonaws.com/%s', $this->bucketName, $this->region, $prefix);
    }

    private function processContent(UploadedFile $file, int $maxSizeBytes = 10_485_760): string
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('File upload failed: ' . $file->getErrorMessage());
        }

        $content = file_get_contents($file->getPathname());

        $image = $this->createImageResource($file);
        if ($image === null) {
            return $content;
        }

        $mimeType = $file->getMimeType();
        $quality = 90;
        $minQuality = 10;

        if (in_array($mimeType, self::APPLE_EXTENSIONS)) {
            $content = $this->convertToJpeg($image, $quality);
        }

        if (strlen($content) <= $maxSizeBytes) {
            return $content;
        }

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
        $newHeight = (int) floor($originalHeight * ( $maxWidth / $originalWidth ));

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

        return sprintf('%s/%s%s.%s', $directory, $filename, MediaRepository::THUMBNAIL_SUFFIX, $extension);
    }

    private function createImageResource(UploadedFile $file): ?\GdImage
    {
        if (!$file->isValid()) {
            return null;
        }

        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'video/')) {
            return null;
        }

        if (!str_starts_with($mimeType, 'image/') || !extension_loaded('gd')) {
            return null;
        }

        if (in_array($mimeType, self::APPLE_EXTENSIONS)) {
            return $this->createImageResourceFromAppleExtensions($file->getPathname());
        }

        $content = file_get_contents($file->getPathname());
        $image = @imagecreatefromstring($content);

        if ($image === false) {
            return null;
        }

        return $this->applyExifOrientation($image, $file->getPathname());
    }

    private function convertToJpeg(\GdImage $image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);

        return ob_get_clean();
    }

    private function encodeImage(\GdImage $image, string $mimeType, int $quality): string
    {
        ob_start();

        match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, null, $quality),
            'image/png' => imagepng($image, null, (int) floor(( 100 - $quality ) / 10)),
            'image/webp' => imagewebp($image, null, $quality),
            default => imagejpeg($image, null, $quality)
        };

        return ob_get_clean();
    }

    private function createImageResourceFromAppleExtensions(string $filePath): ?\GdImage
    {
        $imagick = new \Imagick($filePath);
        $imagick->setImageFormat('jpeg');

        $jpegContent = $imagick->getImageBlob();
        $imagick->clear();
        $imagick->destroy();

        $image = @imagecreatefromstring($jpegContent);

        return $image === false ? null : $image;
    }

    private function applyExifOrientation(\GdImage $image, string $filePath): \GdImage
    {
        $exif = @exif_read_data($filePath);
        if ($exif === false || !isset($exif['Orientation'])) {
            return $image;
        }

        $degrees = match ((int) $exif['Orientation']) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0
        };

        if ($degrees === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $degrees, 0);
        imagedestroy($image);

        return $rotated === false ? $image : $rotated;
    }

    private function buildKey(int $orderId, string $extension): string
    {
        return sprintf('%d/%s.%s', $orderId, Uuid::v4(), $extension);
    }
}
