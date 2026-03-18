<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\ArchiveEventImages\ArchiveEventImagesCommand;
use App\Application\ArchiveEventImages\ArchiveEventImagesHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/archive/create')]
final class ArchiveCreateController extends AbstractController
{
    public const NAME_ARCHIVE_CREATE = 'api_archive_create';

    #[Route('', name: self::NAME_ARCHIVE_CREATE, methods: ['POST'])]
    public function createClient(
        Request $request,
        ArchiveEventImagesHandler $archiveEventImagesHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if ($userRole !== UserRole::ROLE_SUPER_ADMIN) {
            throw new AccessDeniedHttpException();
        }

        $data = json_decode($request->getContent(), true);

        $archiveEventImagesCommand = new ArchiveEventImagesCommand(new OrderIdValueObject($data['order_id']));

        $archiveEventImagesHandler($archiveEventImagesCommand);

        return $this->json([], Response::HTTP_CREATED);
    }
}
