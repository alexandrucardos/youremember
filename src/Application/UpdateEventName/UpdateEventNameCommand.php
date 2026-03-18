<?php

declare(strict_types = 1);

namespace App\Application\UpdateEventName;

use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;

class UpdateEventNameCommand
{
    public function __construct(
        public readonly UuidValueObject $eventUuidValueObject,
        public readonly EventNameValueObject $eventNameValueObject,
        public readonly EventNameFontValueObject $eventNameFontValueObject
    ) {
    }
}
