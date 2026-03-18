<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile\Message;

class IncorrectMimeTypeException extends ProfileBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::INCORRECT_MIME_TYPE);
    }
}
