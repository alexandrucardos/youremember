<?php

namespace App\Application\ListProfileInformation;

class ListProfileViewModel
{
    public function __construct(
        public readonly ProfileViewModel $profileViewModel,
        public readonly MediaViewModel $mediaViewModel
    ) {
    }

    public function toArray(): array
    {
        return [
            'profile' => [
                'id' => $this->profileViewModel->id,
                'name' => $this->profileViewModel->name,
                'name_font' => $this->profileViewModel->nameFont,
                'born_at' => $this->profileViewModel->bornAt,
                'deceased_at' => $this->profileViewModel->departedAt,
                'obituary' => $this->profileViewModel->obituary
            ],
            'media' => [
                'backgroundPictureUrl' => $this->mediaViewModel->backgroundPictureUrl,
                'profilePictureUrl' => $this->mediaViewModel->profilePictureUrl,
                'pictures' => $this->mediaViewModel->picturesUrls
            ]
        ];
    }
}
