<?php

namespace App\Controller\API;

use App\Service\User\UserAddService;
use App\ValueObject\EmailValueObject;
use App\ValueObject\User\UserAddValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user')]
final class UserController extends AbstractController
{
    public const NAME_USER_CLIENT_CREATE = 'api_user_client_create';

    #[Route('/client', name: self::NAME_USER_CLIENT_CREATE, methods: ['POST'])]
    public function create(
        Request        $request,
        UserAddService $userAddService,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userAddVO = new UserAddValueObject(
            email: new EmailValueObject($data['email'] ?? null),
            role: UserRole::ROLE_CLIENT
        );

        $user = $userAddService->add($userAddVO);

        return $this->json([
            'email' => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }
}