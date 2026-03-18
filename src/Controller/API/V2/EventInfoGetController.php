<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\ListEventInformation\ListProfileInformation;
use App\Application\ListEventInformation\ListProfileInformationQuery;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/event/information')]
final class EventInfoGetController extends AbstractController
{
    public const NAME_EVENT_INFORMATION_UUID = 'api_event_information_uuid';
    public const NAME_EVENT_INFORMATION_ORDER_ID = 'api_event_information_order_id';

    #[Route('/eventUuid/{uuid}', name: self::NAME_EVENT_INFORMATION_UUID, methods: ['GET'])]
    public function getEventInformationForUuid(
        Request $request,
        ListProfileInformation $listEventInformation
    ): JsonResponse {
        $listEventInformationQuery = new ListProfileInformationQuery(
            orderId: null,
            uuid: new UuidValueObject($request->attributes->get('uuid'))
        );

        $eventViewModel = $listEventInformation($listEventInformationQuery);

        return $this->json($eventViewModel->toArray());
    }

    #[Route('/oderId/{orderId}', name: self::NAME_EVENT_INFORMATION_ORDER_ID, methods: ['GET'])]
    public function getEventInformationForOrderId(
        Request $request,
        ListProfileInformation $listEventInformation
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles())) {
            throw new AccessDeniedHttpException();
        }

        $listEventInformationQuery = new ListProfileInformationQuery(
            orderId: new OrderIdValueObject($request->attributes->get('orderId')),
            uuid: null
        );

        $eventViewModel = $listEventInformation($listEventInformationQuery);

        return $this->json($eventViewModel->toArray());
    }
}
