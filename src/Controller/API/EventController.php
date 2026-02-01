<?php

namespace App\Controller\API;

use App\Service\Event\EventAddService;
use App\Service\Event\EventDataService;
use App\Service\Event\EventFetchService;
use App\ValueObject\BackgroundImageValueObject;
use App\ValueObject\Event\EventAddValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/event')]
final class EventController extends AbstractController
{
    public const NAME_EVENT_GET = 'api_event_get';
    public const NAME_EVENT_CREATE = 'api_event_create';

    #[Route('/{id}', name: self::NAME_EVENT_GET, methods: ['GET'])]
    public function get(
        Request           $request,
        EventFetchService $eventFetchService,
        EventDataService  $eventDataService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $eventFetchVO = $eventFetchService->fetchByOrderId($orderId);

        $eventDataDto = $eventDataService->fetch($eventFetchVO);

        return $this->json([
            'token' => $eventFetchVO->uuid,
            'name' => $eventFetchVO->name,
            'media' => [
                'backgroundPictureUrl' => $eventDataDto->backgroundPictureUrl,
                'pictures' => $eventDataDto->pictures,
            ],
        ]);
    }

    #[Route('', name: self::NAME_EVENT_CREATE, methods: ['POST'])]
    public function create(
        Request         $request,
        EventAddService $eventAddService,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $eventAddValueObject = new EventAddValueObject(
            orderId: new OrderIdValueObject($data['orderId']),
            name: new EventNameValueObject($data['name']),
            backgroundImage: new BackgroundImageValueObject($data['backgroundImage'] ?? null),
        );

        $event = $eventAddService->add($eventAddValueObject);

        return $this->json([
            'uuid' => $event->getUuid(),
            'orderId' => $event->getOrderId(),
            'name' => $event->getName(),
        ], Response::HTTP_CREATED);
    }
}

