<?php

namespace App\Controller\API\V2;

use App\Application\UpdateProfileObituary\UpdateProfileObituaryCommand;
use App\Application\UpdateProfileObituary\UpdateProfileObituaryHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\EmailValueObject;
use App\ValueObject\ObituaryValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/update/obituary')]
final class ProfileUpdateObituaryController extends AbstractController
{
    public const NAME_PROFILE_OBITUARY_UPDATE = 'api_profile_obituary_update';

    #[Route('/orderId/{order_id}', name: self::NAME_PROFILE_OBITUARY_UPDATE, methods: ['PATCH'])]
    public function update(
        Request $request,
        UpdateProfileObituaryHandler $updateProfileObituaryHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $data = json_decode($request->getContent(), true);

        $updateProfileObituaryCommand = new UpdateProfileObituaryCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)),
            obituaryValueObject: new ObituaryValueObject($data['obituary'])
        );

        $updateProfileObituaryHandler($updateProfileObituaryCommand);

        return $this->json([]);
    }
}
