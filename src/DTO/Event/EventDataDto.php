<?php

namespace App\DTO\Event;

class EventDataDto
{
    public function __construct(
        public string $backgroundPictureUrl,
        public array  $pictures,
    )
    {
    }

}