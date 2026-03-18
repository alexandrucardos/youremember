<?php

declare(strict_types=1);

namespace App\Controller\API\V2;

use App\Application\AddMedia\AddMediaCommand;
use App\Application\AddMedia\AddMediaHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/media/add')]
final class MediaAddController extends AbstractController
{
    public const NAME_MEDIA_ADD = 'api_media_add';

    #[Route('/orderId/{order_id}', name: self::NAME_MEDIA_ADD, methods: ['POST'])]
    public function mediaAdd(
        Request         $request,
        AddMediaHandler $addMediaHandler
    ): JsonResponse
    {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException();
        }

        $addMediaCommand = new AddMediaCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            files: $request->files->get('files', []),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL))
        );

        $addMediaHandler($addMediaCommand);

        return $this->json([], Response::HTTP_CREATED);
    }
}
