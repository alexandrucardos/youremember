<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileObituary;

use App\ValueObject\EmailValueObject;
use App\ValueObject\ObituaryValueObject;
use App\ValueObject\OrderIdValueObject;

class UpdateProfileObituaryCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $userEmail,
        public readonly ObituaryValueObject $obituaryValueObject
    ) {
    }
}
