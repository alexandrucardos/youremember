<?php

namespace App\EventSubscriber;

use App\Domain\Model\Profile\Message\ProfileBaseMsgException;
use App\Exception\Auth\ExpiredException;
use App\Exception\Auth\InvalidHmacException;
use App\Exception\Event\NotFoundException as ProfileNotFoundException;
use App\Exception\Media\NotFoundException as MediaNotFoundException;
use App\Exception\Media\UnauthorizedException as MediaUnauthorizedException;
use App\Exception\User\NotFoundException as UserNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionToHttpResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $errorLogger
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof InvalidHmacException, $exception instanceof ExpiredException => new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_UNAUTHORIZED
            ),
            $exception instanceof ProfileNotFoundException,
                $exception instanceof UserNotFoundException,
                $exception instanceof MediaNotFoundException
            => new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND),
            $exception instanceof MediaUnauthorizedException => new JsonResponse(['error' =>
                $exception->getMessage()], Response::HTTP_FORBIDDEN),
            $exception instanceof \InvalidArgumentException,
                $exception instanceof ProfileBaseMsgException
            => new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST),
            default => $this->logUnexpected($exception)
        };

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    function logUnexpected(\Throwable $e): void
    {
        $this->errorLogger->log(LogLevel::ERROR, $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    }
}
