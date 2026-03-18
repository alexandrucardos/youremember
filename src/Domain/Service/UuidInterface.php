<?php

declare(strict_types = 1);

namespace App\Domain\Service;

interface UuidInterface
{
    public function generate(): string;
}
