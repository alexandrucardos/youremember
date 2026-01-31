<?php

namespace App\ValueObject\Event;

class EventDataValueObject
{
    public function __construct(
        public string $backgroundPictureUrl,
        public array  $pictures,
    )
    {
    }

}