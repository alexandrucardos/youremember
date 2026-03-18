<?php

declare(strict_types = 1);

namespace App\Application\DeleteMedia;

use App\ValueObject\HashValueObject;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;

class DeleteMediaCommand
{
    public function __construct(
        public readonly UuidValueObject $eventUuidValueObject,
        public readonly HashValueObject $userIdentifier,
        public readonly UserRole $userRole,
        public readonly array $filePaths
    ) {
    }
}
