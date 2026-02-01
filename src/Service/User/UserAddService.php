<?php

namespace App\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\ValueObject\User\UserAddValueObject;

final class UserAddService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    )
    {
    }

    public function add(UserAddValueObject $userAddDto): User
    {
        $user = new User();
        $user->setEmail($userAddDto->email->value);
        $user->setHash($userAddDto->hash->value);
        $user->setRole($userAddDto->role);

        $this->userRepository->save($user);

        return $user;
    }
}