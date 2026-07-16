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
            'profile' => $this->profileViewModel->toArray(),
            'media' => $this->mediaViewModel->toArray()
        ];
    }
}
