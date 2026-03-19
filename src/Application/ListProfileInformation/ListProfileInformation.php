<?php

namespace App\Application\ListProfileInformation;

use App\Domain\Model\Profile\ProfileRepositoryInterface;

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
        return $this->eventRepository->fetchEventViewModelForEvent($query->orderId);
    }
}
