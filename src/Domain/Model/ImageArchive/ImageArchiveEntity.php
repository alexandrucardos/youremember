<?php

namespace App\Domain\Model\ImageArchive;

use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;

class ImageArchiveEntity
{
    public function __construct(
        public readonly UuidValueObject  $eventUuidValueObject,
        public readonly EmailValueObject $emailValueObject,
    )
    {
    }
}