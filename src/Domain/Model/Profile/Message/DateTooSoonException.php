<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile\Message;

class DateTooSoonException extends ProfileBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::DATE_TOO_SOON);
    }
}
