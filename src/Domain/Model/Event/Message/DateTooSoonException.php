<?php

declare(strict_types = 1);

namespace App\Domain\Model\Event\Message;

class DateTooSoonException extends EventBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::DATE_TOO_SOON);
    }
}
