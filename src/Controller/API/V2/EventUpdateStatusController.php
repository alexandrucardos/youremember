<?php

namespace App\Controller\API\V2;

use App\Application\UpdateEventStatus\UpdateEventStatusCommand;
use App\Application\UpdateEventStatus\UpdateEventStatusHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/update/event/status')]
final class EventUpdateStatusController extends AbstractController
{
    public const NAME_EVENT_STATUS_UPDATE = 'api_event_status_update';

    #[Route('', name: self::NAME_EVENT_STATUS_UPDATE, methods: ['PATCH'])]
    public function update(
        Request $request,
        UpdateEventStatusHandler $updateEventStatusHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $data = json_decode($request->getContent(), true);

        $updateEventStatusCommand = new UpdateEventStatusCommand(
            orderIdValueObject: new OrderIdValueObject($data['order_id'] ?? null),
            orderStatusValueObject: new OrderStatusValueObject($data['status'] ?? null)
        );

        $updateEventStatusHandler($updateEventStatusCommand);

        return $this->json([]);
    }
}
