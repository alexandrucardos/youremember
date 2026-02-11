<?php

namespace App\Service\Event;

use App\Repository\EventRepository;
use App\ValueObject\Status;

class EventAvailabilityService
{
    public function __construct(
        private readonly EventRepository $eventRepository
    )
    {

    }

    public function __invoke(
        ?string $orderId,
        ?string $uuid,
    ): bool
    {
        if ($orderId !== null) {
            $event = $this->eventRepository->findOneBy(
                [
                    'order_id' => $orderId,
                    'status' => Status::VALID
                ]);

            return $event !== null;
        }

        if ($uuid !== null) {
            $event = $this->eventRepository->findOneBy(
                [
                    'uuid' => $uuid,
                    'status' => Status::VALID
                ]);

            return $event !== null;
        }

        throw new \RuntimeException('Invalid data provided!');
    }
}