<?php

namespace App\ValueObject;

enum UserRole: string
{
    case ROLE_CLIENT = 'role_client';
    case ROLE_GUEST = 'role_guest';
}