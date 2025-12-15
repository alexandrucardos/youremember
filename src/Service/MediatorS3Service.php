<?php

namespace App\Service;

use AsyncAws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediatorS3Service
{
    public function __construct(
        private S3Client $s3,
        private string   $bucketName,
        private string   $region
    )
    {
    }

    public function upload(int $profileId, UploadedFile $file): string
    {
        $key = $profileId . '/' . $file->getClientOriginalName();
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

    function getS3KeyFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['host'], $parsed['path'])) {
            throw new \Exception('Invalid URL');
        }

        $path = ltrim($parsed['path'], '/'); // remove leading /

        return $path;
    }
}
