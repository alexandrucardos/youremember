<?php

namespace App\Service\Bucket;

interface BucketProviderInterface
{
    public function putObject(string $key, string $body, string $contentType): void;

    public function getPresignedUrl(string $key, string $contentType, \DateTimeImmutable $expires): string;

    public function createMultipartUpload(string $key, string $contentType): string;

    public function getPresignedUrlForPart(string $key, string $uploadId, int $partNumber, \DateTimeImmutable $expires): string;

    /**
     * @param array<array{PartNumber: int, ETag: string}> $parts
     */
    public function completeMultipartUpload(string $key, string $uploadId, array $parts): void;

    public function abortMultipartUpload(string $key, string $uploadId): void;

    public function deleteObject(string $key): void;

    public function objectExists(string $key): bool;

    /**
     * @return array<string>
     */
    public function listObjects(string $prefix): array;
}