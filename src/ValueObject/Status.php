<?php

namespace App\ValueObject;

enum Status: string
{
    case VALID = 'valid';
    case INVALID = 'invalid';
}