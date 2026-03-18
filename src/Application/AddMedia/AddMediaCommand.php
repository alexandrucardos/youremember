<?php

declare(strict_types = 1);

namespace App\Application\AddMedia;

use App\ValueObject\HashValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AddMediaCommand
{
    /**
     * @param array<UploadedFile> $files
     */
    public function __construct(
        public readonly UserRole $userRole,
        public readonly HashValueObject $userIdentifier,
        public readonly UuidValueObject $eventUuidValueObject,
        public readonly array $files
    ) {
    }
}
