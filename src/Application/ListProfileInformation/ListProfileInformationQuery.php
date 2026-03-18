<?php

namespace App\Application\ListEventInformation;

use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;

class ListProfileInformationQuery
{
    public function __construct(
        public readonly ?OrderIdValueObject $orderId = null,
        public readonly ?UuidValueObject $uuid = null
    ) {
    }
}
