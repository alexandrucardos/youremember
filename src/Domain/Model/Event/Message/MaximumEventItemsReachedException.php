<?php

declare(strict_types = 1);

namespace App\Domain\Model\Event\Message;

class MaximumEventItemsReachedException extends EventBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::MAXIMUM_MEDIA_ITEMS_REACHED);
    }
}
