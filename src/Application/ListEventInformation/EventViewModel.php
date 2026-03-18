<?php

namespace App\Application\ListEventInformation;

use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\UuidValueObject;

class EventViewModel
{
    public function __construct(
        public readonly UuidValueObject $eventUuid,
        public readonly ?string $eventName,
        public readonly EventNameFontValueObject $eventNameFont,
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
