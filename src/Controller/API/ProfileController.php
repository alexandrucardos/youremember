<?php

namespace App\Controller\API;

use App\Service\Profile\ProfileFetchService;
use App\Service\Profile\ProfileViewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/profile')]
final class ProfileController extends AbstractController
{
    #[Route('/{id}', name: 'api_profile_show', methods: ['GET'])]
    public function show(
        Request $request,
        ProfileFetchService $profileFetchService,
        ProfileViewService $profileViewService,
    ): JsonResponse {
        $profile = $profileFetchService->fetchById($request->attributes->get('id'));
        $mediaData = $profileViewService->buildMediaData($profile);

        return $this->json([
            'id' => $profile->getId(),
            'name' => $profile->getName(),
            'description' => $profile->getDescription(),
            'bornAt' => $profile->getBornAt()?->format('Y-m-d'),
            'deceasedAt' => $profile->getDeceasedAt()?->format('Y-m-d'),
            'status' => $profile->getStatus()?->value,
            'createdAt' => $profile->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $profile->getUpdatedAt()->format(DATE_ATOM),
            'media' => [
                'backgroundPictureUrl' => $mediaData['backgroundPictureUrl'],
                'profilePictureUrl' => $mediaData['profilePictureUrl'],
                'images' => $mediaData['images'],
            ],
        ]);
    }
}

