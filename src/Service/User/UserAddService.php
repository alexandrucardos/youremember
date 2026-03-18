<?php

declare(strict_types = 1);

namespace App\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\ValueObject\User\UserAddValueObject;

final class UserAddService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function add(UserAddValueObject $userAddDto): User
    {
        $user = $this->userRepository->findOneBy(['email' => $userAddDto->email->value]);
        if ($user) {
            return $user;
        }

        $now = new \DateTimeImmutable('now');

        $user = new User();
        $user->setEmail($userAddDto->email->value)->setRole($userAddDto->role)->setCreatedAt($now)->setModifiedAt($now);

        $this->userRepository->save($user);

        return $user;
    }
}
