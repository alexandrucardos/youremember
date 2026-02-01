<?php

namespace App\Service;

use App\Exception\Media\NotFoundException;
use App\Exception\Media\UnauthorizedException;
use App\ValueObject\HashValueObject;
use AsyncAws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediatorS3Service
{
    public const FOLDER_CLIENT = 'client';
    public const FOLDER_PROFILE = 'profile';

    public const PROFILE_BACKGROUND = 'background';

    public function __construct(
        private S3Client $s3,
        private string   $bucketName,
        private string   $region
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
        foreach ($files as $file) {
            $key = sprintf(
                '%d/%s/%s',
                $orderId,
                $folder,
                $file->getClientOriginalName()
            );

            $this->s3->putObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
                'Body' => file_get_contents($file->getPathname()),
                'ContentType' => $file->getMimeType(),
            ]);
        }

        return sprintf(
            'https://%s.s3.amazonaws.com/%s',
            $this->bucketName,
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

        $this->s3->putObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'Body' => file_get_contents($file->getPathname()),
            'ContentType' => $file->getMimeType(),
        ]);

        return sprintf(
            'https://%s.s3.amazonaws.com/%s',
            $this->bucketName,
            $key
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

    public function fetchContentUrls(string $prefix): array
    {
        $result = $this->s3->listObjectsV2([
            'Bucket' => $this->bucketName,
            'Prefix' => $prefix,
        ]);

        $urls = [];

        foreach ($result->getContents() as $object) {
            $key = $object->getKey();

            if ($key === $prefix) {
                continue;
            }

            $urls[] = sprintf(
                'https://%s.s3.%s.amazonaws.com/%s',
                $this->bucketName,
                $this->region,
                $key
            );
        }

        return $urls;
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
        if (!$this->objectExists($key)) {
            throw new NotFoundException('Media not found');
        }

        $this->s3->deleteObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
        ]);
    }

    private function objectExists(string $key): bool
    {
        try {
            $this->s3->headObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
            ])->resolve();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteByUrl(string $url): void
    {
        $key = $this->getS3KeyFromUrl($url);

        $this->deleteByKey($key);
    }
}
