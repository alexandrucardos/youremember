<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\DeleteMedia\DeleteMediaCommand;
use App\Application\DeleteMedia\DeleteMediaHandler;
use App\Domain\Model\User\UserEntity;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\Service\Media\MediaCountService;
use App\Service\Media\MediaDeleteService;
use App\ValueObject\HashValueObject;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/media/delete')]
final class MediaDeleteController extends AbstractController
{
    public const NAME_MEDIA_DELETE = 'api_media_delete';

    #[Route('/eventUuid/{uuid}', name: self::NAME_MEDIA_DELETE, methods: ['DELETE'])]
    public function guestDelete(
        Request $request,
        DeleteMediaHandler $deleteMediaHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        $data = json_decode($request->getContent(), true);

        $urls = $data['urls'] ?? [];

        $deleteMediaCommand = new DeleteMediaCommand(
            eventUuidValueObject: new UuidValueObject($request->attributes->get('uuid')),
            userIdentifier: new HashValueObject($request->headers->get('token')),
            userRole: $userRole,
            filePaths: $urls
        );

        $deleteMediaHandler($deleteMediaCommand);

        return $this->json([], Response::HTTP_OK);
    }
}
