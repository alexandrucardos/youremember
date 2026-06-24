<?php

declare(strict_types = 1);

namespace App\Service\Media;

use App\Entity\Profile;
use App\Exception\Media\MaximumMediaItemsReachedException;
use App\Exception\Profile\NotFoundException;
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
            $profile = $this->entityManager->getRepository(Profile::class)->findOneBy(['order_id' => $orderId]);

            if (!$profile instanceof Profile) {
                throw new NotFoundException('Profile not found');
            }

            $this->entityManager->lock($profile, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->refresh($profile);

            if (( $profile->getMediaCount() + $count ) >= $profile->getMaxMediaCount()) {
                throw new MaximumMediaItemsReachedException('Maximum media items reached');
            }

            $profile->setMediaCount($profile->getMediaCount() + $count);

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
            $profile = $this->entityManager->getRepository(Profile::class)->findOneBy(['order_id' => $orderId]);

            if (!$profile instanceof Profile) {
                throw new NotFoundException('Profile not found');
            }

            $this->entityManager->lock($profile, LockMode::PESSIMISTIC_WRITE);

            $newCount = max(0, $profile->getMediaCount() - 1);
            $profile->setMediaCount($newCount);

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
