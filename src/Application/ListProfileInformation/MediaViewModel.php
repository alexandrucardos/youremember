<?php

namespace App\Application\ListProfileInformation;

class MediaViewModel
{
    /**
     * @param array<string> $picturesUrls
     */
    private function __construct(
        public readonly string $backgroundPictureUrl,
        public readonly string $profilePictureUrl,
        public readonly array $picturesUrls
    ) {
    }

    public static function fromArray(array $media): self
    {
        return new self(
            backgroundPictureUrl: $media['backgroundPictureUrl'],
            profilePictureUrl: $media['profilePictureUrl'],
            picturesUrls: $media['pictures']
        );
    }
}
