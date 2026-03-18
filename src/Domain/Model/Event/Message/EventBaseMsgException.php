<?php

declare(strict_types = 1);

namespace App\Domain\Model\Event\Message;

class EventBaseMsgException extends \RuntimeException
{
    protected const EVENT_NOT_VALID = 4000;
    protected const INCORRECT_MIME_TYPE = 4001;
    protected const DATE_TOO_SOON = 4002;
    protected const MAXIMUM_MEDIA_ITEMS_REACHED = 4003;
}
