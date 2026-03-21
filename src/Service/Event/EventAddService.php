<?php

namespace App\Service\Event;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Entity\Profile;
use App\Exception\User\NotFoundException;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;

final class EventAddService
{
    public function __construct(
        private readonly ProfileRepository $eventRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public function add(EmailValueObject $email, OrderIdValueObject $orderId): Profile
    {
        $user = $this->userRepository->findBy(['email' => $email->value]);

        if (empty($user)) {
            throw new NotFoundException('User not found');
        }

        $event = (new Profile())
            ->setExternalId($this->generateExternalId())
            ->setOrderId($orderId->value)
            ->setUser(reset($user));

        $this->eventRepository->save($event);

        return $event;
    }

    private function generateExternalId(): int
    {
        return random_int(100000, 999999);
    }
}
