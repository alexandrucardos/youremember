<?php

declare(strict_types = 1);

namespace App\Application\InitiateMultipartMediaUpload;

use App\ValueObject\HashValueObject;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;

class InitiateMediaUploadCommand
{
    public function __construct(
        public readonly UuidValueObject $eventUuidValueObject,
        public readonly HashValueObject $userIdentifier,
        public readonly UserRole $userRole,
        public readonly string $filename,
        public readonly string $mimeType
    ) {
    }
}
