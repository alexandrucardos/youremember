<?php

namespace App\Service\Bucket;

use AsyncAws\S3\S3Client;

class AwsProviderService implements BucketProviderInterface
{
    public function __construct(
        private readonly S3Client $s3Client,
        private readonly string $bucketName,
    )
    {
    }

    public function putObject(string $key, string $body, string $contentType): void
    {
        $this->s3Client->putObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'Body' => $body,
            'ContentType' => $contentType,
        ]);
    }

    public function deleteObject(string $key): void
    {
        $this->s3Client->deleteObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
        ]);
    }

    public function objectExists(string $key): bool
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
            ])->resolve();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string>
     */
    public function listObjects(string $prefix): array
    {
        $result = $this->s3Client->listObjectsV2([
            'Bucket' => $this->bucketName,
            'Prefix' => $prefix,
        ]);

        $keys = [];

        foreach ($result->getContents() as $object) {
            $key = $object->getKey();

            if ($key !== $prefix) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}