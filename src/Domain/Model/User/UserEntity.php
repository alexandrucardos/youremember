<?php

declare(strict_types = 1);

namespace App\Domain\Model\User;

use App\ValueObject\EmailValueObject;

class UserEntity
{
    public const ADMIN_USER_IDENTIFIER = 'client';

    public function __construct(
        public readonly EmailValueObject $emailValueObject
    ) {
    }
}
