<?php

namespace App\Service\Bucket;

interface BucketProviderInterface
{
    public function putObject(string $key, string $body, string $contentType): void;

    public function deleteObject(string $key): void;

    public function objectExists(string $key): bool;

    /**
     * @return array<string>
     */
    public function listObjects(string $prefix): array;
}