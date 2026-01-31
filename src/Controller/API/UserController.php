<?php

namespace App\Controller\API;

use App\Service\User\UserAddService;
use App\ValueObject\EmailValueObject;
use App\ValueObject\HashValueObject;
use App\ValueObject\User\UserAddValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user')]
final class UserController extends AbstractController
{
    #[Route('', name: 'api_user_add', methods: ['POST'])]
    public function add(
        Request        $request,
        UserAddService $userAddService,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userAddVO = new UserAddValueObject(
            email: new EmailValueObject($data['email'] ?? null),
            hash: new HashValueObject($data['hash'] ?? null),
        );

        $user = $userAddService->add($userAddVO);

        return $this->json([
            'email' => $user->getEmail(),
            'hash' => $user->getHash(),
        ], Response::HTTP_CREATED);
    }
}