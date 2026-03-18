<?php

namespace App\Controller\API\V2;

use App\Application\UpdateProfileBackground\UpdateProfileBackgroundCommand;
use App\Application\UpdateProfileBackground\UpdateProfileBackgroundHandler;
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

#[Route('/api/v2/update/event/background')]
final class EventUpdateBackgroundController extends AbstractController
{
    public const NAME_EVENT_BACKGROUND_UPDATE = 'api_event_background_update';

    #[Route('/orderId/{order_id}', name: self::NAME_EVENT_BACKGROUND_UPDATE, methods: ['POST'])]
    public function update(
        Request $request,
        UpdateProfileBackgroundHandler $updateEventBackgroundHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $updateEventStatusCommand = new UpdateProfileBackgroundCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userRole: $userRole,
            backgroundFile: $request->files->get('file', [])
        );

        $updateEventBackgroundHandler($updateEventStatusCommand);

        return $this->json([]);
    }
}
