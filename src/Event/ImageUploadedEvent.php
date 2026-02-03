<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ImageUploadedEvent extends Event
{
    public const NAME = 'image.uploaded';

    public function __construct(
        public readonly int $orderId,
    )
    {
    }
}