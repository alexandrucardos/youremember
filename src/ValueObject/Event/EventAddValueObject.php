<?php

namespace App\ValueObject\Event;

use App\ValueObject\BackgroundImageValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;

class EventAddValueObject
{
    public function __construct(
        public readonly OrderIdValueObject         $orderId,
        public readonly EventNameValueObject       $name,
        public readonly BackgroundImageValueObject $backgroundImage,
    )
    {
    }
}