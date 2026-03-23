<?php

namespace App\Controller\API;

use App\Application\UpdateProfilePicture\UpdateProfilePictureCommand;
use App\Application\UpdateProfilePicture\UpdateProfilePictureHandler;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\UserRole;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/update/profile/profile-picture')]
final class ProfileUpdateProfilePictureController extends AbstractController
{
    public const NAME_PROFILE_PROFILE_PICTURE_UPDATE = 'api_profile_profile_picture_update';

    #[Route('/orderId/{order_id}', name: self::NAME_PROFILE_PROFILE_PICTURE_UPDATE, methods: ['POST'])]
    public function update(
        Request $request,
        UpdateProfilePictureHandler $updateProfilePictureHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $updateProfilePictureCommand = new UpdateProfilePictureCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)),
            profilePictureFile: $request->files->get('file', [])
        );

        $updateProfilePictureHandler($updateProfilePictureCommand);

        return $this->json([]);
    }
}
