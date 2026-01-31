<?php

namespace App\ValueObject\User;

use App\ValueObject\EmailValueObject;
use App\ValueObject\HashValueObject;
use App\ValueObject\UserRole;

class UserAddValueObject
{
    public function __construct(
        public readonly EmailValueObject $email,
        public readonly HashValueObject  $hash,
        public readonly UserRole         $role = UserRole::ROLE_GUEST,
    )
    {
        $hasEmail = $this->email->value !== null;
        $hasHash = $this->hash->value !== null;

        if ($hasEmail && $hasHash) {
            throw new \InvalidArgumentException('Cannot set both email and hash. Provide only one.');
        }

        if (!$hasEmail && !$hasHash) {
            throw new \InvalidArgumentException('Either email or hash must be provided.');
        }
    }
}