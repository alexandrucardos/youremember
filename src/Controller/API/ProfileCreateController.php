<?php

declare(strict_types = 1);

namespace App\Controller\API;

use App\Application\AddProfile\AddProfileCommand;
use App\Application\AddProfile\AddProfileHandler;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileIdValueObject;
use App\Domain\ValueObject\UserRole;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/profile')]
final class ProfileCreateController extends AbstractController
{
    public const NAME_PROFILE_CREATE = 'api_profile_create';

    #[Route('', name: self::NAME_PROFILE_CREATE, methods: ['POST'])]
    public function createClient(
        Request $request,
        AddProfileHandler $addProfileHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if ($userRole !== UserRole::ROLE_SUPER_ADMIN) {
            throw new AccessDeniedHttpException();
        }

        $data = json_decode($request->getContent(), true);

        $addProfileCommand = new AddProfileCommand(
            orderIdValueObject: new OrderIdValueObject($data['order_id']),
            emailValueObject: new EmailValueObject($data['client_email']),
            profileIdValueObject: new ProfileIdValueObject($data['profile_id'])
        );

        $addProfileHandler($addProfileCommand);

        return $this->json([], Response::HTTP_CREATED);
    }
}
