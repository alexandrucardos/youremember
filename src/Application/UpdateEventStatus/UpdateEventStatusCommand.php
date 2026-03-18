<?php

declare(strict_types = 1);

namespace App\Application\UpdateEventStatus;

use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use App\ValueObject\UuidValueObject;

class UpdateEventStatusCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly OrderStatusValueObject $orderStatusValueObject
    ) {
    }
}
