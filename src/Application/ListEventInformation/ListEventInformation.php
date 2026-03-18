<?php

namespace App\Application\ListEventInformation;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\Exception\ListEventInformationQueryNullException;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;

class ListEventInformation
{
    public function __construct(
        public readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(
        ListEventInformationQuery $query
    ): EventViewModel
    {
        if ($query->uuid === null && $query->orderId === null) {
            throw new ListEventInformationQueryNullException('Invalid values on query');
        }

        if ($query->orderId) {
            $orderId = $query->orderId->value;
        } else {
            $orderId = $this->eventRepository->fetchOrderIdForUuid($query->uuid->value);
        }

        return $this->eventRepository->fetchEventViewModelForEvent(new OrderIdValueObject($orderId));
    }
}
