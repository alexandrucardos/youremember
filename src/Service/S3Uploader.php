<?php

namespace App\Service;

use AsyncAws\S3\S3Client;

class S3Uploader
{
    public function __construct(
        private S3Client $s3,
        private string   $bucketName
    )
    {
    }

    public function upload(string $key, string $content, string $contentType = 'application/octet-stream'): string
    {
        $this->s3->putObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'Body' => $content,
            'ContentType' => $contentType,
        ]);

        return sprintf(
            'https://%s.s3.amazonaws.com/%s',
            $this->bucketName,
            $key
        );
    }
}
