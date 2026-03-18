<?php

declare(strict_types = 1);

namespace App\Application\SubscribeForArchiveArchive;

use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;

class SubscribeForArchiveCommand
{
    public function __construct(
        public readonly UuidValueObject $uuidValueObject,
        public readonly EmailValueObject $emailValueObject
    ) {
    }
}
