<?php

namespace App\Application\ListProfileInformation;

use App\Domain\ValueObject\DateValueObject;
use App\Entity\Profile;

class ProfileViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly string $nameFont,
        public readonly ?DateValueObject $bornAt,
        public readonly ?DateValueObject $departedAt,
        public readonly ?string $obituary
    ) {
    }

    public static function fromEntity(Profile $profile): self
    {
        return new self(
            id: $profile->getId(),
            name: $profile->getName(),
            nameFont: $profile->getNameFont(),
            bornAt: $profile->getBornAt(),
            departedAt: $profile->getDepartedAt(),
            obituary: $profile->getObituary()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value,
            'name' => $this->name,
            'name_font' => $this->nameFont,
            'born_at' => $this->bornAt?->value,
            'deceased_at' => $this->departedAt?->value,
            'obituary' => $this->obituary ?? null
        ];
    }
}
