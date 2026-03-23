<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile\Message;

class ProfileIdExistsException extends ProfileBaseMsgException
{
    public function __construct(string $message)
    {
        parent::__construct($message, self::PROFILE_ID_EXISTS);
    }
}
