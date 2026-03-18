<?php

declare(strict_types=1);

namespace App\Application\DeleteMedia;

use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;

class DeleteMediaCommand
{
    public function __construct(
        public readonly OrderIdValueObject $orderIdValueObject,
        public readonly array              $filePaths,
        public readonly EmailValueObject   $userEmail
    )
    {
    }
}
