<?php

declare(strict_types = 1);

namespace App\Application\UpdateProfileBackground;

use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UpdateProfileBackgroundCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject $userEmail,
        public readonly UploadedFile $backgroundFile
    ) {
    }
}
