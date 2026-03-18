<?php

declare(strict_types = 1);

namespace App\Application\ArchiveEventImages;

use App\Domain\Model\Event\EventRepositoryInterface;

class ArchiveEventImagesHandler
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository
    ) {
    }

    public function __invoke(ArchiveEventImagesCommand $command): void
    {
        $this->eventRepository->generateArchiveForOrder($command->eventOrderIdValueObject);
    }
}
