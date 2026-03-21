<?php

declare(strict_types = 1);

namespace App\Application\AddProfile;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileIdValueObject;

class AddProfileCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $emailValueObject,
        public readonly ProfileIdValueObject $profileIdValueObject
    ) {
    }
}
