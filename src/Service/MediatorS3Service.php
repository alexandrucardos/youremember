<?php

namespace App\Service;

use App\Exception\Media\NotFoundException;
use App\Exception\Media\UnauthorizedException;
use App\Service\Bucket\BucketProviderInterface;
use App\ValueObject\HashValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediatorS3Service
{
    public const FOLDER_CLIENT = 'client';
    public const FOLDER_PROFILE = 'profile';

    public const PROFILE_BACKGROUND = 'background';

    public function __construct(
        private readonly BucketProviderInterface $bucketProvider,
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
    ): string
    {
        $key = '';

        foreach ($files as $file) {
            $key = sprintf(
                '%d/%s/%s',
                $orderId,
                $folder,
                $file->getClientOriginalName()
            );

            $this->bucketProvider->putObject(
                $key,
                file_get_contents($file->getPathname()),
                $file->getMimeType()
            );
        }

        return $this->buildUrlFromKey($key);
    }

    private function buildUrlFromKey(string $key): string
    {
        return sprintf(
            'https://%s.s3.%s.amazonaws.com/%s',
            $this->bucketName,
            $this->region,
            $key
        );
    }

    public function uploadSingle(int $profileId, UploadedFile $file, string $type): string
    {
        $key = sprintf(
            '%d/%s/%s',
            $profileId,
            self::FOLDER_PROFILE,
            $type
        );

        $this->bucketProvider->putObject(
            $key,
            file_get_contents($file->getPathname()),
            $file->getMimeType()
        );

        return $this->buildUrlFromKey($key);
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

    public function fetchContentUrls(string $prefix): array
    {
        $keys = $this->bucketProvider->listObjects($prefix);

        return array_map(
            fn(string $key) => $this->buildUrlFromKey($key),
            $keys
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

        return ltrim($parsed['path'], '/');
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
        if (!$this->bucketProvider->objectExists($key)) {
            throw new NotFoundException('Media not found');
        }

        $this->bucketProvider->deleteObject($key);
    }

    public function deleteByUrl(string $url): void
    {
        $key = $this->getS3KeyFromUrl($url);

        $this->deleteByKey($key);
    }
}