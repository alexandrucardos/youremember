<?php

declare(strict_types=1);

namespace App\Domain\Model\Profile\Exception;

use App\Exception\Event\BaseEventException;

class InvalidProfileIdException extends BaseEventException
{
}
