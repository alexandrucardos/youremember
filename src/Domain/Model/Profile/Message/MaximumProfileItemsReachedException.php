<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile\Message;

class MaximumProfileItemsReachedException extends ProfileBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::MAXIMUM_MEDIA_ITEMS_REACHED);
    }
}
