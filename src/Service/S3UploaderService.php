<?php

namespace App\Service;

use App\Entity\User;
use AsyncAws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3UploaderService
{
    public function __construct(
        private S3Client $s3,
        private string   $bucketName
    )
    {
    }

//    public function content(string $key, string $content, string $contentType = 'application/octet-stream'): string
    public function upload(User $user, UploadedFile $file): string
    {
        $key = $user->getId() . '/' . $file->getClientOriginalName();
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
}
