<?php

declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Controller\API\ProfileInfoGetController;
use App\Service\FrontendTokenParserService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecurityValidationRequestSubscriber implements EventSubscriberInterface
{
    public const REQUEST_TOKEN = 'token';
    public const REQUEST_ATTRIBUTE_USER_ROLE = 'user_role';
    public const REQUEST_ATTRIBUTE_EMAIL = 'email';

    public function __construct(
        private readonly FrontendTokenParserService $tokenParser
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

        if ($routeName === ProfileInfoGetController::NAME_PROFILE_INFORMATION_PROFILE_ID) {
            return;
        }

        $token = $request->headers->get(self::REQUEST_TOKEN);

        if ($token === null) {
            throw new UnauthorizedHttpException('Token', 'Missing token header');
        }

        [$userRole, $email] = $this->tokenParser->validateTokenAndGetUserInfo($token);

        $request->attributes->set(self::REQUEST_ATTRIBUTE_USER_ROLE, $userRole);
        $request->attributes->set(self::REQUEST_ATTRIBUTE_EMAIL, $email);
    }
}
