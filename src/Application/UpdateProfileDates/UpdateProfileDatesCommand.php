<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileDates;

use App\ValueObject\DateValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;

class UpdateProfileDatesCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $userEmail,
        public readonly DateValueObject $bornAtValueObject,
        public readonly DateValueObject $departedAtValueObject
    ) {
    }
}
