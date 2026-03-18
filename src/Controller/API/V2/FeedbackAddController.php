<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\AddFeedback\AddFeedbackCommand;
use App\Application\AddFeedback\AddFeedbackHandler;
use App\Application\SubscribeForArchiveArchive\SubscribeForArchiveArchiveHandler;
use App\Application\SubscribeForArchiveArchive\SubscribeForArchiveCommand;
use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/feedback/add')]
final class FeedbackAddController extends AbstractController
{
    public const NAME_FEEDBACK_ADD = 'api_feedback_add';

    #[Route('/eventUuid/{uuid}', name: self::NAME_FEEDBACK_ADD, methods: ['POST'])]
    public function addFeedbackByUuid(
        Request $request,
        AddFeedbackHandler $addFeedbackHandler
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $addFeedbackCommand = new AddFeedbackCommand(
            feedbackValueObject: new FeedbackValueObject($data['feedback'] ?? null),
            uuidValueObject: new UuidValueObject($request->attributes->get('uuid'))
        );

        $addFeedbackHandler($addFeedbackCommand);

        return $this->json([]);
    }
}
