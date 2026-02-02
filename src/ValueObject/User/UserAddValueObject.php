<?php

namespace App\ValueObject\User;

use App\ValueObject\EmailValueObject;
use App\ValueObject\UserRole;

class UserAddValueObject
{
    public function __construct(
        public readonly EmailValueObject $email,
        public readonly UserRole         $role = UserRole::ROLE_GUEST,
    )
    {
    }
}