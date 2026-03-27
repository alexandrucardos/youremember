<?php

declare(strict_types = 1);

namespace App\Controller\API;

use App\Application\GetProfileIdForOrderId\GetProfileIdForOrderId;
use App\Application\GetProfileIdForOrderId\GetProfileIdForOrderIdQuery;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\UserRole;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/profileId')]
final class ProfileIdGetForOrderIdController extends AbstractController
{
    public const NAME_PROFILE_ID_ORDER_ID = 'api_profile_id_order_id';

    #[Route('/oderId/{orderId}', name: self::NAME_PROFILE_ID_ORDER_ID, methods: ['GET'])]
    public function getEventInformationForOrderId(
        Request $request,
        GetProfileIdForOrderId $getProfileIdForOrderId
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException();
        }

        $getProfileIdForOrderIdQuery =
            new GetProfileIdForOrderIdQuery(orderIdValueObject: new OrderIdValueObject($request->attributes->get(
                'orderId'
            )));

        $profileId = $getProfileIdForOrderId($getProfileIdForOrderIdQuery);

        return $this->json(['profile_id' => $profileId]);
    }
}
