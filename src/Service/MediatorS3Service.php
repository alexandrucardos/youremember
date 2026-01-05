<?php

namespace App\Service;

use AsyncAws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediatorS3Service
{
    public const FOLDER_IMAGES = 'pictures';
    public const FOLDER_PROFILE = 'profile';

    public const PROFILE_PICTURE = 'profile';
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
    public function uploadMultiple(int $profileId, array $files): string
    {
        foreach ($files as $file) {
            $key = sprintf(
                '%d/%s/%s',
                $profileId,
                self::FOLDER_IMAGES,
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

    public function deleteContent(string $url): bool
    {
        $key = $this->getS3KeyFromUrl($url);

        try {
            $result = $this->s3->deleteObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
            ]);

            if ($result['DeleteMarker'] ?? false) {
                return true;
            }

            return true;
        } catch (\Throwable $e) {
            error_log('S3 delete failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getS3KeyFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['host'], $parsed['path'])) {
            throw new \Exception('Invalid URL');
        }

        $path = ltrim($parsed['path'], '/'); // remove leading /

        return $path;
    }
}
