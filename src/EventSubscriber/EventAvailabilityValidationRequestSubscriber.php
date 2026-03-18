<?php

declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Controller\old\EventController;
use App\Controller\old\MediaController;
use App\Exception\Event\EventInvalidException;
use App\Service\Event\EventAvailabilityService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class EventAvailabilityValidationRequestSubscriber implements EventSubscriberInterface
{
    private const ROUTES = [
        EventController::NAME_EVENT_CLIENT_GET,
        EventController::NAME_EVENT_CLIENT_UPDATE,
        EventController::NAME_EVENT_CLIENT_PAGE_URL_GET,
        EventController::NAME_EVENT_CLIENT_NAME_GET,
        EventController::NAME_EVENT_GUEST_GET_BY_UUID, //uuid

        MediaController::NAME_MEDIA_CLIENT_GET,
        MediaController::NAME_MEDIA_CLIENT_ADD,
        //        MediaController::NAME_MEDIA_CLIENT_DELETE,

        MediaController::NAME_MEDIA_CLIENT_BACKGROUND_ADD,
        MediaController::NAME_MEDIA_CLIENT_BACKGROUND_GET,

        MediaController::NAME_MEDIA_GUEST_ADD // uuid
        //        MediaController::NAME_MEDIA_GUEST_DELETE,
    ];

    public function __construct(
        private readonly EventAvailabilityService $availabilityService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        if (!in_array($routeName, self::ROUTES, true)) {
            return;
        }

        $orderId = $request->attributes->get('orderId');
        $uuid = $request->attributes->get('uuid');

        $shouldContinue = ( $this->availabilityService )($orderId, $uuid);

        if ($shouldContinue === false) {
            throw new EventInvalidException('The event is no longer valid!');
        }
    }
}
