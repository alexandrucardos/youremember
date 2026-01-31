<?php

namespace App\DTO\Event;

class EventFetchDto
{
    public function __construct(
        public string  $uuid,
        public string  $name,
        public int     $orderId,
        public ?string $backgroundImage,
    )
    {
    }

}