<?php

namespace App\EventSubscriber;

use App\Controller\API\EventController;
use App\Controller\API\MediaController;
use App\Controller\API\UserController;
use App\Service\FrontendTokenParserService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecurityValidationRequestSubscriber implements EventSubscriberInterface
{
    private const ROUTES = [
        EventController::NAME_EVENT_CLIENT_CREATE,
        EventController::NAME_EVENT_CLIENT_GET,
        EventController::NAME_EVENT_CLIENT_UPDATE,
        UserController::NAME_USER_CLIENT_CREATE,
        MediaController::NAME_MEDIA_CLIENT_ADD,
        MediaController::NAME_MEDIA_CLIENT_DELETE,
        EventController::NAME_EVENT_CLIENT_PAGE_URL_GET,
    ];

    public function __construct(
        private readonly FrontendTokenParserService $tokenParser,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
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

        $token = $request->headers->get('token');

        if ($token === null) {
            throw new UnauthorizedHttpException('Token', 'Missing token header');
        }

        $this->tokenParser->validateToken($token);
    }
}
