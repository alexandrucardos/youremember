<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile\Message;

class ProfileNotValidException extends ProfileBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::EVENT_NOT_VALID);
    }
}
