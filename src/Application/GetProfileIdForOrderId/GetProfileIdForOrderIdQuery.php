<?php

declare(strict_types = 1);

namespace App\Application\GetProfileIdForOrderId;

use App\Domain\ValueObject\OrderIdValueObject;

class GetProfileIdForOrderIdQuery
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject
    ) {
    }
}
