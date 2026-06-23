<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileName;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;
use App\Domain\ValueObject\ProfileNameValueObject;

class UpdateProfileNameCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $userEmail,
        public readonly ProfileNameValueObject $profileNameValueObject,
        public readonly ProfileNameFontValueObject $profileNameFontValueObject
    ) {
    }
}
