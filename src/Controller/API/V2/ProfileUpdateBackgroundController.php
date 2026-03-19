<?php

namespace App\Controller\API\V2;

use App\Application\UpdateProfileBackground\UpdateProfileBackgroundCommand;
use App\Application\UpdateProfileBackground\UpdateProfileBackgroundHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/update/profile/background')]
final class ProfileUpdateBackgroundController extends AbstractController
{
    public const NAME_PROFILE_BACKGROUND_UPDATE = 'api_profile_background_update';

    #[Route('/orderId/{order_id}', name: self::NAME_PROFILE_BACKGROUND_UPDATE, methods: ['POST'])]
    public function update(
        Request $request,
        UpdateProfileBackgroundHandler $updateProfileBackgroundHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $updateProfileBackgroundCommand = new UpdateProfileBackgroundCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)),
            backgroundFile: $request->files->get('file', [])
        );

        $updateProfileBackgroundHandler($updateProfileBackgroundCommand);

        return $this->json([]);
    }
}
