<?php

declare(strict_types = 1);

namespace App\ValueObject\Event;

class EventDataValueObject
{
    public function __construct(
        public string $backgroundPictureUrl,
        public array $pictures
    ) {
    }
}
