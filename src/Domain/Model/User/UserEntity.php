<?php

declare(strict_types=1);

namespace App\Domain\Model\User;

use App\Domain\ValueObject\EmailValueObject;

class UserEntity
{
    public function __construct(
        public readonly EmailValueObject $emailValueObject
    )
    {
    }
}
