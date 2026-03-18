<?php

declare(strict_types = 1);

namespace App\Service\Media;

use App\Entity\Event;
use App\Exception\Event\NotFoundException;
use App\Exception\Media\MaximumMediaItemsReachedException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class MediaCountService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function incrementByOrderId(int $orderId, int $count): void
    {
        $this->entityManager->beginTransaction();

        try {
            $event = $this->entityManager->getRepository(Event::class)->findOneBy(['order_id' => $orderId]);

            if (!$event instanceof Event) {
                throw new NotFoundException('Event not found');
            }

            $this->entityManager->lock($event, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->refresh($event);

            if (( $event->getMediaCount() + $count ) >= $event->getMaxMediaCount()) {
                throw new MaximumMediaItemsReachedException('Maximum media items reached');
            }

            $event->setMediaCount($event->getMediaCount() + $count);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    public function decrementByUrl(string $url): void
    {
        $orderId = $this->extractOrderIdFromUrl($url);

        $this->entityManager->beginTransaction();

        try {
            $event = $this->entityManager->getRepository(Event::class)->findOneBy(['order_id' => $orderId]);

            if (!$event instanceof Event) {
                throw new NotFoundException('Event not found');
            }

            $this->entityManager->lock($event, LockMode::PESSIMISTIC_WRITE);

            $newCount = max(0, $event->getMediaCount() - 1);
            $event->setMediaCount($newCount);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    private function extractOrderIdFromUrl(string $url): int
    {
        $parsed = parse_url($url);

        if (!isset($parsed['path'])) {
            throw new \InvalidArgumentException('Invalid URL');
        }

        $path = ltrim($parsed['path'], '/');
        $parts = explode('/', $path);

        if (count($parts) < 1 || !is_numeric($parts[0])) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        return (int) $parts[0];
    }
}
