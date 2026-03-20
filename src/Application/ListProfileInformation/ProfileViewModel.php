<?php

namespace App\Application\ListProfileInformation;

use App\ValueObject\ProfileIdValueObject;
use App\ValueObject\ProfileNameFontValueObject;

class ProfileViewModel
{
    public function __construct(
        public readonly ProfileIdValueObject $eventUuid,
        public readonly ?string $eventName,
        public readonly ProfileNameFontValueObject $eventNameFont,
        public readonly MediaViewModel $media
    ) {
    }

    public function toArray(): array
    {
        return [
            'token' => $this->eventUuid->value,
            'name' => $this->eventName,
            'font' => $this->eventNameFont->value,
            'media' => [
                'backgroundPictureUrl' => $this->media->backgroundPictureUrl,
                'pictures' => $this->media->picturesUrls
            ]
        ];
    }
}
