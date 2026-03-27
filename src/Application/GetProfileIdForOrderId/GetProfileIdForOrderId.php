<?php

declare(strict_types = 1);

namespace App\Application\GetProfileIdForOrderId;

use App\Domain\Model\Profile\ProfileRepositoryInterface;

class GetProfileIdForOrderId
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function __invoke(GetProfileIdForOrderIdQuery $query): ?int
    {
        return $this->profileRepository->getExistingProfileIdForOrderId($query->orderIdValueObject);
    }
}
