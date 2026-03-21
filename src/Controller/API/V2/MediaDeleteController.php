<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\DeleteMedia\DeleteMediaCommand;
use App\Application\DeleteMedia\DeleteMediaHandler;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\UserRole;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/media/delete')]
final class MediaDeleteController extends AbstractController
{
    public const NAME_MEDIA_DELETE = 'api_media_delete';

    #[Route('/orderId/{order_id}', name: self::NAME_MEDIA_DELETE, methods: ['DELETE'])]
    public function guestDelete(
        Request $request,
        DeleteMediaHandler $deleteMediaHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException();
        }

        $data = json_decode($request->getContent(), true);

        $urls = $data['urls'] ?? [];

        $deleteMediaCommand = new DeleteMediaCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            filePaths: $urls,
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL))
        );

        $deleteMediaHandler($deleteMediaCommand);

        return $this->json([], Response::HTTP_OK);
    }
}
