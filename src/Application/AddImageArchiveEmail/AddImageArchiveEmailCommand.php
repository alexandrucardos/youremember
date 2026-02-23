<?php

namespace App\Application\AddImageArchiveEmail;

use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;

class AddImageArchiveEmailCommand
{
    public function __construct(
        public readonly UuidValueObject  $uuidValueObject,
        public readonly EmailValueObject $emailValueObject,
    )
    {
    }
}