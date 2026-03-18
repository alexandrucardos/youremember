<?php

declare(strict_types = 1);

namespace App\Service;

use App\Domain\Service\UuidInterface;
use Symfony\Component\Uid\Uuid;

class UuidSymfonyImplementation implements UuidInterface
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
