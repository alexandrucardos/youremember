<?php

namespace App\Controller\API\V2;

use App\Application\UpdateEventName\UpdateProfileNameCommand;
use App\Application\UpdateEventName\UpdateProfileNameHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/update/name/font')]
final class EventUpdateNameAndFontController extends AbstractController
{
    public const NAME_EVENT_NAME_UPDATE = 'api_event_name_update';

    #[Route('/eventUuid/{uuid}', name: self::NAME_EVENT_NAME_UPDATE, methods: ['PATCH'])]
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
            eventUuidValueObject: new UuidValueObject($request->attributes->get('uuid')),
            eventNameValueObject: new EventNameValueObject($data['name']),
            eventNameFontValueObject: new EventNameFontValueObject($data['font'])
        );

        $eventNameHandler($updateEventNameCommand);

        return $this->json([]);
    }
}
