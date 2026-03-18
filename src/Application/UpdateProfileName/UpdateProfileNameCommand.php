<?php

declare(strict_types=1);

namespace App\Application\UpdateProfileName;

use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\ProfileNameValueObject;

class UpdateProfileNameCommand
{
    public function __construct(
        public readonly OrderIdValueObject         $orderIdValueObject,
        public readonly EmailValueObject           $userEmail,
        public readonly ProfileNameValueObject     $eventNameValueObject,
        public readonly ProfileNameFontValueObject $eventNameFontValueObject
    )
    {
    }
}
