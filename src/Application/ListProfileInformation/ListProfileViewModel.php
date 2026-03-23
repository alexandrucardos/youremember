<?php

namespace App\Application\ListProfileInformation;

use App\Domain\ValueObject\DateValueObject;
use App\Domain\ValueObject\ProfileIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;

class ProfileViewModel
{
    public function __construct(
        public readonly ProfileIdValueObject       $profileId,
        public readonly ?string                    $profileName,
        public readonly ProfileNameFontValueObject $profileNameFont,
        public readonly ?DateValueObject           $bornAt,
        public readonly ?DateValueObject           $departedAt,
        public readonly ?string                    $obituary,
        public readonly MediaViewModel             $media
    )
    {
    }

    public function toArray(): array
    {
        return [
            'profile' => [
                'id' => $this->profileId->value,
                'name' => $this->profileName,
                'name_font' => $this->profileNameFont->value,
                'born_at' => $this->bornAt?->value,
                'deceased_at' => $this->departedAt?->value,
                'obituary' => $this->obituary ?? null,
            ],
            'media' => [
                'backgroundPictureUrl' => $this->media->backgroundPictureUrl,
                'profilePictureUrl' => $this->media->profilePictureUrl,
                'pictures' => $this->media->picturesUrls
            ]
        ];
    }
}
