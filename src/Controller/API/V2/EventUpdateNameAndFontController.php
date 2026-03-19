<?php

namespace App\Controller\API\V2;

use App\Application\UpdateProfileName\UpdateProfileNameCommand;
use App\Application\UpdateProfileName\UpdateProfileNameHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\ProfileNameValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/update/name/font')]
final class EventUpdateNameAndFontController extends AbstractController
{
    public const NAME_EVENT_NAME_UPDATE = 'api_event_name_update';

    #[Route('/orderId/{order_id}', name: self::NAME_EVENT_NAME_UPDATE, methods: ['PATCH'])]
    public function update(
        Request $request,
        UpdateProfileNameHandler $eventNameHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $data = json_decode($request->getContent(), true);

        $updateEventNameCommand = new UpdateProfileNameCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)),
            eventNameValueObject: new ProfileNameValueObject($data['name']),
            eventNameFontValueObject: new ProfileNameFontValueObject($data['font'])
        );

        $eventNameHandler($updateEventNameCommand);

        return $this->json([]);
    }
}
