<?php

namespace App\Application\ListProfileInformation;

use App\Domain\ValueObject\ProfileIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;

class ProfileViewModel
{
    public function __construct(
        public readonly ProfileIdValueObject $profileId,
        public readonly ?string $profileName,
        public readonly ProfileNameFontValueObject $profileNameFont,
        public readonly MediaViewModel $media
    ) {
    }

    public function toArray(): array
    {
        return [
            'profileId' => $this->profileId->value,
            'name' => $this->profileName,
            'font' => $this->profileNameFont->value,
            'media' => [
                'backgroundPictureUrl' => $this->media->backgroundPictureUrl,
                'pictures' => $this->media->picturesUrls
            ]
        ];
    }
}
