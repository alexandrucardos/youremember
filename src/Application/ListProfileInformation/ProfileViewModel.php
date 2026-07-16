<?php

namespace App\Application\ListProfileInformation;

use App\Entity\Profile;

class ProfileViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly string $nameFont,
        public readonly string $title,
        public readonly ?string $bornAt,
        public readonly ?string $departedAt,
        public readonly ?string $obituary
    ) {
    }

    public static function fromEntity(Profile $profile): self
    {
        return new self(
            id: $profile->getExternalId(),
            name: $profile->getName(),
            nameFont: $profile->getNameFont(),
            title: $profile->getTitle(),
            bornAt: $profile->getBornAt()?->format('Y-m-d'),
            departedAt: $profile->getDepartedAt()?->format('Y-m-d'),
            obituary: $profile->getObituary()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_font' => $this->nameFont,
            'title' => $this->title,
            'born_at' => $this->bornAt,
            'deceased_at' => $this->departedAt,
            'obituary' => $this->obituary
        ];
    }
}
