<?php

declare(strict_types = 1);

namespace App\Domain\Model\Event\Message;

class IncorrectMimeTypeException extends EventBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::INCORRECT_MIME_TYPE);
    }
}
