<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileObituary;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\ObituaryValueObject;
use App\Domain\ValueObject\OrderIdValueObject;

class UpdateProfileObituaryCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $userEmail,
        public readonly ObituaryValueObject $obituaryValueObject
    ) {
    }
}
