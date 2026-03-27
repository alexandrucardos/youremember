<?php

namespace App\Application\ListProfileInformation;

use App\Domain\Model\Profile\Exception\EitherProfileIdOrOrderIdShouldBeSetException;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileIdValueObject;

class ListProfileInformationQuery
{
    public function __construct(
        public readonly ?OrderIdValueObject $orderIdValueObject = null,
        public readonly ?ProfileIdValueObject $profileIdValueObject = null
    ) {
        if ($orderIdValueObject === null && $profileIdValueObject === null) {
            throw new EitherProfileIdOrOrderIdShouldBeSetException('Either profile id or order id should be set');
        }
    }
}
