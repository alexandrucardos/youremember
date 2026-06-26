<?php

namespace App\Controller\API;

use App\Application\UpdateProfileNameAndTitle\UpdateProfileNameAndTitleCommand;
use App\Application\UpdateProfileNameAndTitle\UpdateProfileNameAndTitleHandler;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;
use App\Domain\ValueObject\ProfileNameValueObject;
use App\Domain\ValueObject\ProfileTitleValueObject;
use App\Domain\ValueObject\UserRole;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/v1/update/name/font/title')]
final class ProfileUpdateNameFontTitleController extends AbstractController
{
    public const NAME_PROFILE_NAME_UPDATE = 'api_profile_name_update';

    #[Route('/orderId/{order_id}', name: self::NAME_PROFILE_NAME_UPDATE, methods: ['PATCH'])]
    public function update(
        Request $request,
        UpdateProfileNameAndTitleHandler $profileNameHandler,
        TranslatorInterface $translator
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException('Admins role missing');
        }

        $data = json_decode($request->getContent(), true);

        $updateProfileNameCommand = new UpdateProfileNameAndTitleCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)),
            profileNameValueObject: new ProfileNameValueObject($data['name'], $translator),
            profileNameFontValueObject: new ProfileNameFontValueObject($data['font']),
            titleValueObject: new ProfileTitleValueObject($data['title'], $translator)
        );

        $profileNameHandler($updateProfileNameCommand);

        return $this->json([]);
    }
}
