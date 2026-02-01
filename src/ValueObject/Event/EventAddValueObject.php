<?php

namespace App\ValueObject\Event;

use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;

class EventAddValueObject
{
    public function __construct(
        public readonly EmailValueObject   $email,
        public readonly OrderIdValueObject $orderId,
    )
    {
    }
}