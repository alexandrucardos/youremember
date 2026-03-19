<?php

namespace App\Application\ListProfileInformation;

use App\ValueObject\OrderIdValueObject;

class ListProfileInformationQuery
{
    public function __construct(
        public readonly OrderIdValueObject $orderId
    ) {
    }
}
