<?php

namespace App\Service\Event;

use App\Entity\Profile;
use App\Exception\User\NotFoundException;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use App\ValueObject\Event\EventAddValueObject;

final class EventAddService
{
    public function __construct(
        private readonly ProfileRepository $eventRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public function add(EventAddValueObject $eventAddDto): Profile
    {
        $user = $this->userRepository->findBy(['email' => $eventAddDto->email->value]);

        if (empty($user)) {
            throw new NotFoundException('User not found');
        }

        $event = (new Profile())
            ->setUuid($this->generateUuid())
            ->setOrderId($eventAddDto->orderId->value)
            ->setUser(reset($user));

        $this->eventRepository->save($event);

        return $event;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(( ord($data[6]) & 0x0f ) | 0x40);
        $data[8] = chr(( ord($data[8]) & 0x3f ) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
