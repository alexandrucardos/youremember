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
    #[Route('/{id}', name: 'api_event_show', methods: ['GET'])]
    public function show(
        Request           $request,
        EventFetchService $eventFetchService,
        EventDataService  $eventDataService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $eventFetchDto = $eventFetchService->fetchByOrderId($orderId);

        $eventDataDto = $eventDataService->fetch($eventFetchDto);

        return $this->json([
            'token' => $eventFetchDto->uuid,
            'name' => $eventFetchDto->name,
            'media' => [
                'backgroundPictureUrl' => $eventDataDto->backgroundPictureUrl,
                'pictures' => $eventDataDto->pictures,
            ],
        ]);
    }

    #[Route('', name: 'api_event_add', methods: ['POST'])]
    public function add(
        Request         $request,
        EventAddService $eventAddService,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $eventAddDto = new EventAddValueObject(
            orderId: new OrderIdValueObject($data['orderId']),
            name: new EventNameValueObject($data['name']),
            backgroundImage: new BackgroundImageValueObject($data['backgroundImage'] ?? null),
        );

        $event = $eventAddService->add($eventAddDto);

        return $this->json([
            'uuid' => $event->getUuid(),
            'orderId' => $event->getOrderId(),
            'name' => $event->getName(),
        ], Response::HTTP_CREATED);
    }
}

