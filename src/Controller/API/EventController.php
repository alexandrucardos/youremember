<?php

namespace App\Controller\API;

use App\Service\Event\EventDataService;
use App\Service\Event\EventFetchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        $eventFetchDto = $eventFetchService->fetchByOrderId($request->attributes->get('orderId'));
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
}

