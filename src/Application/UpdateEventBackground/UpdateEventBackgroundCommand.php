<?php

declare(strict_types = 1);

namespace App\Application\UpdateEventBackground;

use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UpdateEventBackgroundCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly UserRole $userRole,
        public readonly UploadedFile $backgroundFile
    ) {
    }
}
