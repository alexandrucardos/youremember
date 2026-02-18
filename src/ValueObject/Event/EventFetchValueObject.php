<?php

namespace App\ValueObject\Event;

class EventFetchValueObject
{
    public function __construct(
        public string  $uuid,
        public string  $name,
        public string  $nameFont,
        public int     $orderId,
        public ?string $backgroundImage,
    )
    {
    }

}