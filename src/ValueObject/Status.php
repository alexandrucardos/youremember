<?php

declare(strict_types = 1);

namespace App\ValueObject;

enum Status: string
{
    case VALID = 'valid';
    case INVALID = 'invalid';
}
