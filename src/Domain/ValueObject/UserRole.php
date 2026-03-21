<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

enum UserRole: string
{
    case ROLE_SUPER_ADMIN = 'role_super_admin';
    case ROLE_ADMIN = 'role_admin';
    case ROLE_GUEST = 'role_guest';
    case ROLE_CLIENT = 'role_client';

    public static function getAdminRoles(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN, self::ROLE_CLIENT];
    }
}
