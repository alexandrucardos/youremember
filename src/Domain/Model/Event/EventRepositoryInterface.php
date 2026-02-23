<?php

namespace App\Domain\Model\Event;

use App\Domain\Model\Event\Exception\EventNotFoundException;

interface EventRepositoryInterface
{
    /**
     * @throws EventNotFoundException()
     */
    public function saveFeedbackForEvent(EventEntity $eventEntity): void;

    /**
     * @throws EventNotFoundException()
     */
    public function saveArchiveEmailForEvent(EventEntity $eventEntity): void;
}