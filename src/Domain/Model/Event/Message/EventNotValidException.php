<?php

declare(strict_types = 1);

namespace App\Domain\Model\Event\Message;

class EventNotValidException extends EventBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::EVENT_NOT_VALID);
    }
}
