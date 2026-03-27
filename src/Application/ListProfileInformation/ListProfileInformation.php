<?php

namespace App\Application\ListProfileInformation;

use App\Domain\Model\Profile\ProfileRepositoryInterface;

class ListProfileInformation
{
    public function __construct(
        public readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(
        ListProfileInformationQuery $query
    ): ListProfileViewModel
    {
        if ($query->orderIdValueObject === null) {
            $orderId = $this->profileRepository->getExistingOrderIdForProfileId($query->profileIdValueObject);
        } else {
            $orderId = $query->orderIdValueObject->value;
        }

        return $this->profileRepository->fetchProfileViewModelForOrderId($orderId);
    }
}
