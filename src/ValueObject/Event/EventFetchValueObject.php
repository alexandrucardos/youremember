<?php

namespace App\ValueObject\Event;

class EventFetchValueObject
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