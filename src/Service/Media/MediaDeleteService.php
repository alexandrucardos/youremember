<?php

declare(strict_types = 1);

namespace App\Service\Media;

use App\Exception\Media\UnauthorizedException;
use App\Repository\MediaRepository;
use App\ValueObject\HashValueObject;

final class MediaDeleteService
{
    public function __construct(
        private readonly MediaRepository $mediaRepository
    ) {
    }

    public function deleteContent(string $url, HashValueObject $hash): void
    {
        $key = $this->getS3KeyFromUrl($url);
        $folder = $this->extractFolderFromKey($key);

        if ($folder !== $hash->value) {
            throw new UnauthorizedException('Not authorized to delete this media');
        }

        $this->deleteByKey($key);
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

    private function extractFolderFromKey(string $key): string
    {
        $parts = explode('/', $key);

        if (count($parts) < 2) {
            throw new \InvalidArgumentException('Invalid key format');
        }

        return $parts[1];
    }

    private function deleteByKey(string $key): void
    {
        $this->mediaRepository->softDeleteByPath($key);
    }
}
