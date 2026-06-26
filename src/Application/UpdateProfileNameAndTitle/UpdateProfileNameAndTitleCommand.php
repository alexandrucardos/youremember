<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileNameAndTitle;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;
use App\Domain\ValueObject\ProfileNameValueObject;
use App\Domain\ValueObject\ProfileTitleValueObject;

class UpdateProfileNameAndTitleCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $userEmail,
        public readonly ProfileNameValueObject $profileNameValueObject,
        public readonly ProfileNameFontValueObject $profileNameFontValueObject,
        public readonly ProfileTitleValueObject $titleValueObject
    ) {
    }
}
