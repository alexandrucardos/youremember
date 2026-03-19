<?php

declare(strict_types = 1);

namespace App\Service\Bucket;

use Psr\Log\LoggerInterface;

/**
 * Mock S3 Provider Service for testing
 * This service implements BucketProviderInterface but does not actually interact with S3
 * All operations are logged but not executed against real S3 infrastructure
 */
class MockS3ProviderService implements BucketProviderInterface
{
    private array $uploadedObjects = [];
    private array $multipartUploads = [];
    private array $existingObjects = [];

    public function __construct(
        private readonly string $bucketName,
        private readonly string $projectDir,
        private readonly LoggerInterface $logger
    ) {
    }

    public function putObject(string $key, string $body, string $contentType): void
    {
        $this->logger->info('[MOCK S3] putObject called', [
            'bucket' => $this->bucketName,
            'key' => $key,
            'contentType' => $contentType,
            'bodyLength' => strlen($body)
        ]);

        $this->uploadedObjects[$key] = [
            'body' => $body,
            'contentType' => $contentType,
            'uploadedAt' => new \DateTimeImmutable()
        ];
    }

    public function getPresignedUrl(string $key, string $contentType, \DateTimeImmutable $expires): string
    {
        $this->logger->info('[MOCK S3] getPresignedUrl called', [
            'bucket' => $this->bucketName,
            'key' => $key,
            'contentType' => $contentType,
            'expires' => $expires->format('Y-m-d H:i:s')
        ]);

        return sprintf(
            'https://mock-s3.example.com/%s/%s?expires=%s',
            $this->bucketName,
            $key,
            $expires->getTimestamp()
        );
    }

    public function createMultipartUpload(string $key, string $contentType): string
    {
        $uploadId = uniqid('mock-upload-', true);

        $this->logger->info('[MOCK S3] createMultipartUpload called', [
            'bucket' => $this->bucketName,
            'key' => $key,
            'contentType' => $contentType,
            'uploadId' => $uploadId
        ]);

        $this->multipartUploads[$uploadId] = [
            'key' => $key,
            'contentType' => $contentType,
            'parts' => [],
            'createdAt' => new \DateTimeImmutable()
        ];

        return $uploadId;
    }

    public function getPresignedUrlForPart(
        string $key,
        string $uploadId,
        int $partNumber,
        \DateTimeImmutable $expires
    ): string {
        $this->logger->info('[MOCK S3] getPresignedUrlForPart called', [
            'bucket' => $this->bucketName,
            'key' => $key,
            'uploadId' => $uploadId,
            'partNumber' => $partNumber,
            'expires' => $expires->format('Y-m-d H:i:s')
        ]);

        return sprintf(
            'https://mock-s3.example.com/%s/%s?uploadId=%s&partNumber=%d&expires=%s',
            $this->bucketName,
            $key,
            $uploadId,
            $partNumber,
            $expires->getTimestamp()
        );
    }

    public function completeMultipartUpload(string $key, string $uploadId, array $parts): void
    {
        $this->logger->info('[MOCK S3] completeMultipartUpload called', [
            'bucket' => $this->bucketName,
            'key' => $key,
            'uploadId' => $uploadId,
            'partsCount' => count($parts)
        ]);

        if (isset($this->multipartUploads[$uploadId])) {
            $this->multipartUploads[$uploadId]['parts'] = $parts;
            $this->multipartUploads[$uploadId]['completedAt'] = new \DateTimeImmutable();

            // Move to uploaded objects
            $this->uploadedObjects[$key] = [
                'multipartUploadId' => $uploadId,
                'parts' => $parts,
                'completedAt' => new \DateTimeImmutable()
            ];

            unset($this->multipartUploads[$uploadId]);
        }
    }

    public function abortMultipartUpload(string $key, string $uploadId): void
    {
        $this->logger->info('[MOCK S3] abortMultipartUpload called', [
            'bucket' => $this->bucketName,
            'key' => $key,
            'uploadId' => $uploadId
        ]);

        unset($this->multipartUploads[$uploadId]);
    }

    public function deleteObject(string $key): void
    {
        $this->logger->info('[MOCK S3] deleteObject called', [
            'bucket' => $this->bucketName,
            'key' => $key
        ]);

        unset($this->uploadedObjects[$key]);
        unset($this->existingObjects[$key]);
    }

    public function objectExists(string $key): bool
    {
        $exists = isset($this->uploadedObjects[$key]) || isset($this->existingObjects[$key]);

        $this->logger->info('[MOCK S3] objectExists called', [
            'bucket' => $this->bucketName,
            'key' => $key,
            'exists' => $exists
        ]);

        return $exists;
    }

    public function listObjects(string $prefix): array
    {
        $objects = array_filter(array_keys($this->uploadedObjects), fn(string $key) => str_starts_with($key, $prefix));

        $existingObjects = array_filter(array_keys($this->existingObjects), fn(string $key) => str_starts_with(
            $key,
            $prefix
        ));

        $allObjects = array_unique([...$objects, ...$existingObjects]);

        $this->logger->info('[MOCK S3] listObjects called', [
            'bucket' => $this->bucketName,
            'prefix' => $prefix,
            'count' => count($allObjects)
        ]);

        return array_values($allObjects);
    }

    public function downloadFilesForPaths(array $fileUrls): void
    {
        $this->logger->info('[MOCK S3] downloadFilesForPaths called', [
            'bucket' => $this->bucketName,
            'fileUrlsCount' => count($fileUrls)
        ]);

        // In mock mode, we don't actually download files
        // This prevents any S3 operations during testing
    }

    /**
     * Helper method for testing: simulate object existence
     */
    public function simulateObjectExists(string $key): void
    {
        $this->existingObjects[$key] = [
            'simulatedAt' => new \DateTimeImmutable()
        ];
    }

    /**
     * Helper method for testing: get uploaded objects
     */
    public function getUploadedObjects(): array
    {
        return $this->uploadedObjects;
    }

    /**
     * Helper method for testing: get active multipart uploads
     */
    public function getActiveMultipartUploads(): array
    {
        return $this->multipartUploads;
    }

    /**
     * Helper method for testing: reset state
     */
    public function reset(): void
    {
        $this->uploadedObjects = [];
        $this->multipartUploads = [];
        $this->existingObjects = [];
    }
}
