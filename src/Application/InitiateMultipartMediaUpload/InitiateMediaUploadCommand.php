<?php

declare(strict_types=1);

namespace App\Application\InitiateMultipartMediaUpload;

use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;

class InitiateMediaUploadCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly EmailValueObject   $userEmail,
        public readonly string             $filename,
        public readonly string             $mimeType
    )
    {
    }
}
