<?php

declare(strict_types = 1);

namespace App\Service\User;

use App\Repository\UserRepository;

final class UserAddService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }
}
