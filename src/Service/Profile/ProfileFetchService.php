<?php

namespace App\Service\Profile;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProfileFetchService
{
    public function __construct(
        private readonly ProfileRepository $profileRepository,
    ) {
    }

    public function fetchById(mixed $idRaw): Profile
    {
        $id = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new NotFoundHttpException('Profile not found.');
        }

        $profile = $this->profileRepository->find($id);

        if (!$profile instanceof Profile) {
            throw new NotFoundHttpException('Profile not found.');
        }

        return $profile;
    }
}

