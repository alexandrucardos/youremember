<?php

namespace App\Controller\API\V2;

use App\Application\UpdateProfileDates\UpdateProfileDatesCommand;
use App\Application\UpdateProfileDates\UpdateProfileDatesHandler;
use App\Domain\ValueObject\DateValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\UserRole;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/update/dates')]
final class ProfileUpdateDatesController extends AbstractController
{
    public const NAME_PROFILE_DATES_UPDATE = 'api_profile_dates_update';

    #[Route('/orderId/{order_id}', name: self::NAME_PROFILE_DATES_UPDATE, methods: ['PATCH'])]
    public function update(
        Request $request,
        UpdateProfileDatesHandler $updateProfileDatesHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $data = json_decode($request->getContent(), true);

        $updateProfileDatesCommand = new UpdateProfileDatesCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)),
            bornAtValueObject: new DateValueObject($data['born_at']),
            departedAtValueObject: new DateValueObject($data['departed_at'])
        );

        $updateProfileDatesHandler($updateProfileDatesCommand);

        return $this->json([]);
    }
}
