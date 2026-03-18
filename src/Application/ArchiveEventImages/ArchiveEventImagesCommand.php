<?php

declare(strict_types = 1);

namespace App\Application\ArchiveEventImages;

use App\ValueObject\OrderIdValueObject;

class ArchiveEventImagesCommand
{
    public function __construct(
        public readonly OrderIdValueObject $eventOrderIdValueObject
    ) {
    }
}
