<?php

declare(strict_types = 1);

namespace App\Application\AddMedia;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AddMediaCommand
{
    /**
     * @param array<UploadedFile> $files
     */
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly array $files,
        public readonly EmailValueObject $userEmail
    ) {
    }
}
