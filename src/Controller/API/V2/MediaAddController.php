<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\AddMedia\AddMediaCommand;
use App\Application\AddMedia\AddMediaHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\HashValueObject;
use App\ValueObject\UuidValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/media/add')]
final class MediaAddController extends AbstractController
{
    public const NAME_MEDIA_ADD = 'api_media_add';

    #[Route('/eventUuid/{uuid}', name: self::NAME_MEDIA_ADD, methods: ['POST'])]
    public function mediaAdd(
        Request $request,
        AddMediaHandler $addMediaHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        $addMediaCommand = new AddMediaCommand(
            userRole: $userRole,
            userIdentifier: new HashValueObject($request->headers->get('token')),
            eventUuidValueObject: new UuidValueObject($request->attributes->get('uuid')),
            files: $request->files->get('files', [])
        );

        $addMediaHandler($addMediaCommand);

        return $this->json([], Response::HTTP_CREATED);
    }
}
