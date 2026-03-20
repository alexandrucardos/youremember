<?php

declare(strict_types = 1);

namespace App\Service\Media;

use App\Repository\MediaRepository;

final class MediaDeleteService
{
    public function __construct(
        private readonly MediaRepository $mediaRepository
    ) {
    }

    public function deleteByUrl(string $url): void
    {
        //todo make it by batch delete
        $key = $this->getS3KeyFromUrl($url);

        $this->deleteByKey($key);
    }

    private function getS3KeyFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['host'], $parsed['path'])) {
            throw new \InvalidArgumentException('Invalid URL');
        }

        return urldecode(ltrim($parsed['path'], '/'));
    }

    private function deleteByKey(string $key): void
    {
        $this->mediaRepository->softDeleteByPath($key);
    }
}
