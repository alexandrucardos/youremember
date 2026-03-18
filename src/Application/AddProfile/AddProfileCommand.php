<?php

declare(strict_types = 1);

namespace App\Application\AddEvent;

use App\Domain\ValueObject\EventStartDateValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;

class AddProfileCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $emailValueObject,
        public readonly EventStartDateValueObject $eventStartDateValueObject,
        public readonly OrderStatusValueObject $orderStatusValueObject,
        public readonly bool $needsManualProcessing
    ) {
    }
}
