<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\SubscribeForArchiveArchive\SubscribeForArchiveArchiveHandler;
use App\Application\SubscribeForArchiveArchive\SubscribeForArchiveCommand;
use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/request/archive')]
final class SubscribeForArchiveController extends AbstractController
{
    public const NAME_SUBSCRIBE_FOR_ARCHIVE = 'api_subscribe_for_archive';

    #[Route('/eventUuid/{uuid}', name: self::NAME_SUBSCRIBE_FOR_ARCHIVE, methods: ['POST'])]
    public function addFeedbackByUuid(
        Request $request,
        SubscribeForArchiveArchiveHandler $subscribeForArchiveArchiveHandler
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $subscribeForArchiveCommand = new SubscribeForArchiveCommand(
            uuidValueObject: new UuidValueObject($request->attributes->get('uuid')),
            emailValueObject: new EmailValueObject($data['email'] ?? null)
        );

        $subscribeForArchiveArchiveHandler($subscribeForArchiveCommand);

        return $this->json([]);
    }
}
