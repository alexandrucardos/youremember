<?php

namespace App\Application\ListEventInformation;

use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ListProfileInformationQueryNullException;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;

class ListProfileInformation
{
    public function __construct(
        public readonly ProfileRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(
        ListProfileInformationQuery $query
    ): ProfileViewModel
    {
        if ($query->uuid === null && $query->orderId === null) {
            throw new ListProfileInformationQueryNullException('Invalid values on query');
        }

        if ($query->orderId) {
            $orderId = $query->orderId->value;
        } else {
            $orderId = $this->eventRepository->fetchOrderIdForUuid($query->uuid->value);
        }

        return $this->eventRepository->fetchEventViewModelForEvent(new OrderIdValueObject($orderId));
    }
}
