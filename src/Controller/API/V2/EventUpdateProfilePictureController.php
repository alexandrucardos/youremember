<?php

namespace App\Controller\API\V2;

use App\Application\UpdateProfilePicture\UpdateProfilePictureCommand;
use App\Application\UpdateProfilePicture\UpdateProfilePictureHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/update/event/profile-picture')]
final class EventUpdateProfilePictureController extends AbstractController
{
    public const NAME_EVENT_PROFILE_PICTURE_UPDATE = 'api_event_profile_picture_update';

    #[Route('/orderId/{order_id}', name: self::NAME_EVENT_PROFILE_PICTURE_UPDATE, methods: ['POST'])]
    public function update(
        Request $request,
        UpdateProfilePictureHandler $updateEventProfilePictureHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $updateEventProfilePictureCommand = new UpdateProfilePictureCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)),
            profilePictureFile: $request->files->get('file', [])
        );

        $updateEventProfilePictureHandler($updateEventProfilePictureCommand);

        return $this->json([]);
    }
}
