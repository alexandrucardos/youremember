<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\AddEvent\AddEventCommand;
use App\Application\AddEvent\AddEventHandler;
use App\Domain\ValueObject\EventStartDateValueObject;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use App\ValueObject\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/event')]
final class EventCreateController extends AbstractController
{
    public const NAME_EVENT_CREATE = 'api_event_create';

    #[Route('', name: self::NAME_EVENT_CREATE, methods: ['POST'])]
    public function createClient(
        Request $request,
        AddEventHandler $addEventHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException();
        }

        $data = json_decode($request->getContent(), true);

        $addEventCommand = new AddEventCommand(
            orderIdValueObject: new OrderIdValueObject($data['order_id']),
            emailValueObject: new EmailValueObject($data['client_email']),
            eventStartDateValueObject: new EventStartDateValueObject(( new \DateTimeImmutable('now +90 days') )->format(
                'Y-m-d H:i:s'
            )),
            //            eventStartDateValueObject: new EventStartDateValueObject($data['event_start_date']),
            orderStatusValueObject: new OrderStatusValueObject($data['order_status']),
            needsManualProcessing: $data['needs_manual_processing']
        );

        $addEventHandler($addEventCommand);

        return $this->json([], Response::HTTP_CREATED);
    }
}
