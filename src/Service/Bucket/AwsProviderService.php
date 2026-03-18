<?php

declare(strict_types = 1);

namespace App\Service\Bucket;

use AsyncAws\S3\Input\AbortMultipartUploadRequest;
use AsyncAws\S3\Input\CompleteMultipartUploadRequest;
use AsyncAws\S3\Input\CreateMultipartUploadRequest;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\Input\UploadPartRequest;
use AsyncAws\S3\S3Client;
use AsyncAws\S3\ValueObject\CompletedMultipartUpload;
use AsyncAws\S3\ValueObject\CompletedPart;

class AwsProviderService implements BucketProviderInterface
{
    public function __construct(
        private readonly S3Client $s3Client,
        private readonly string $bucketName,
        private readonly string $projectDir
    ) {
    }

    public function putObject(string $key, string $body, string $contentType): void
    {
        $this->s3Client->putObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'Body' => $body,
            'ContentType' => $contentType
        ]);
    }

    public function getPresignedUrl(string $key, string $contentType, \DateTimeImmutable $expires): string
    {
        return $this->s3Client->presign(new PutObjectRequest([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'ContentType' => $contentType
        ]), $expires);
    }

    public function createMultipartUpload(string $key, string $contentType): string
    {
        $result = $this->s3Client->createMultipartUpload(new CreateMultipartUploadRequest([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'ContentType' => $contentType
        ]));

        return $result->getUploadId();
    }

    public function getPresignedUrlForPart(
        string $key,
        string $uploadId,
        int $partNumber,
        \DateTimeImmutable $expires
    ): string {
        return $this->s3Client->presign(new UploadPartRequest([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber
        ]), $expires);
    }

    /**
     * @param array<array{PartNumber: int, ETag: string}> $parts
     */
    public function completeMultipartUpload(string $key, string $uploadId, array $parts): void
    {
        $completedParts = array_map(
            static fn(array $part) => new CompletedPart(['PartNumber' => $part['PartNumber'], 'ETag' => $part['ETag']]),
            $parts
        );

        $this->s3Client
            ->completeMultipartUpload(new CompleteMultipartUploadRequest([
                'Bucket' => $this->bucketName,
                'Key' => $key,
                'UploadId' => $uploadId,
                'MultipartUpload' => new CompletedMultipartUpload(['Parts' => $completedParts])
            ]))
            ->resolve();
    }

    public function abortMultipartUpload(string $key, string $uploadId): void
    {
        $this->s3Client
            ->abortMultipartUpload(new AbortMultipartUploadRequest([
                'Bucket' => $this->bucketName,
                'Key' => $key,
                'UploadId' => $uploadId
            ]))
            ->resolve();
    }

    public function deleteObject(string $key): void
    {
        $this->s3Client->deleteObject([
            'Bucket' => $this->bucketName,
            'Key' => $key
        ]);
    }

    public function objectExists(string $key): bool
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucketName,
                'Key' => $key
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
            'Prefix' => $prefix
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

    public function downloadFilesForPaths(array $fileUrls): void
    {
        $firstFileUrl = reset($fileUrls);

        $folderName = pathinfo($firstFileUrl, PATHINFO_DIRNAME);

        $folders = explode('/', $folderName);

        $mainFolderName = reset($folders);

        $folder = sprintf('%s/archive/%s', $this->projectDir, $mainFolderName);

        if (!is_dir($folder)) {
            if (!mkdir($folder, 0777, true) && !is_dir($folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
        }

        foreach ($fileUrls as $fileUrl) {
            $key = ltrim(parse_url($fileUrl, PHP_URL_PATH), '/');

            $savePath = sprintf('%s/archive/%s/%s', $this->projectDir, $mainFolderName, basename($key));

            try {
                $this->s3Client->headObject([
                    'Bucket' => $this->bucketName,
                    'Key' => $key
                ]);

                $result = $this->s3Client->getObject([
                    'Bucket' => $this->bucketName,
                    'Key' => $key
                ]);

                file_put_contents($savePath, $result->getBody());

                if (!file_exists($savePath)) {
                    echo "Warning: File not created at $savePath\n";
                } else {
                    echo "Downloaded: $savePath\n";
                }
            } catch (\Exception $e) {
                echo 'S3 Error: ' . $e->getMessage() . "\n";
            }
        }
    }
}
