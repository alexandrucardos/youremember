<?php

declare(strict_types = 1);

namespace App\Controller\API;

use App\Application\ListProfileInformation\ListProfileInformation;
use App\Application\ListProfileInformation\ListProfileInformationQuery;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\UserRole;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/profile/information')]
final class ProfileInfoGetController extends AbstractController
{
    public const NAME_EVENT_INFORMATION_ORDER_ID = 'api_event_information_order_id';

    #[Route('/oderId/{orderId}', name: self::NAME_EVENT_INFORMATION_ORDER_ID, methods: ['GET'])]
    public function getEventInformationForOrderId(
        Request $request,
        ListProfileInformation $listEventInformation
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles())) {
            throw new AccessDeniedHttpException();
        }

        $listEventInformationQuery =
            new ListProfileInformationQuery(orderId: new OrderIdValueObject($request->attributes->get('orderId')));

        $eventViewModel = $listEventInformation($listEventInformationQuery);

        return $this->json($eventViewModel->toArray());
    }
}
