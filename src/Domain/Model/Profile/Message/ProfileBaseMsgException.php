<?php

declare(strict_types = 1);

namespace App\Domain\Model\Profile\Message;

class ProfileBaseMsgException extends \RuntimeException
{
    protected const PROFILE_NOT_VALID = 4000;
    protected const INCORRECT_MIME_TYPE = 4001;
    protected const DATE_TOO_SOON = 4002;
    protected const MAXIMUM_MEDIA_ITEMS_REACHED = 4003;
    protected const DATE_IN_FUTURE = 4004;
    protected const PROFILE_ID_EXISTS = 4005;
}
