<?php

namespace App\EventSubscriber;

use App\Exception\Auth\ExpiredException;
use App\Exception\Auth\InvalidHmacException;
use App\Exception\Auth\InvalidStructureException;
use App\Exception\Event\NotFoundException as EventNotFoundException;
use App\Exception\Media\NotFoundException as MediaNotFoundException;
use App\Exception\Media\UnauthorizedException as MediaUnauthorizedException;
use App\Exception\User\NotFoundException as UserNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionToHttpResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof InvalidStructureException,
                $exception instanceof InvalidHmacException,
                $exception instanceof ExpiredException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_UNAUTHORIZED
            ),

            $exception instanceof EventNotFoundException,
                $exception instanceof UserNotFoundException,
                $exception instanceof MediaNotFoundException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_NOT_FOUND
            ),

            $exception instanceof MediaUnauthorizedException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_FORBIDDEN
            ),

            $exception instanceof \InvalidArgumentException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST
            ),

            default => null,
        };

        if ($response !== null) {
            $event->setResponse($response);
        }
    }
}