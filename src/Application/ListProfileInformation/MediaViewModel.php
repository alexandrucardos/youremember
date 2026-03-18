<?php

namespace App\Application\ListEventInformation;

class MediaViewModel
{
    /**
     * @param array<string> $picturesUrls
     */
    public function __construct(
        public readonly string $backgroundPictureUrl,
        public readonly array $picturesUrls
    ) {
    }
}
