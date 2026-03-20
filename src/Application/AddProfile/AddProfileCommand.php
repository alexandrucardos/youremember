<?php

declare(strict_types = 1);

namespace App\Application\AddProfile;

use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\ProfileIdValueObject;

class AddProfileCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $emailValueObject,
        public readonly ProfileIdValueObject $profileIdValueObject
    ) {
    }
}
