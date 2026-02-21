<?php

namespace App\Controller\API;

use App\Application\AddFeedback\AddFeedbackCommand;
use App\Application\AddFeedback\AddFeedbackHandler;
use App\Application\AddImageArchiveEmail\AddImageArchiveEmailCommand;
use App\Application\AddImageArchiveEmail\AddImageArchiveEmailHandler;
use App\Domain\ValueObject\FeedbackValueObject;
use App\Service\Event\EventAddService;
use App\Service\Event\EventFetchService;
use App\Service\Event\EventMediaFetchService;
use App\Service\Event\EventUpdateService;
use App\ValueObject\EmailValueObject;
use App\ValueObject\Event\EventAddValueObject;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/event')]
final class EventController extends AbstractController
{
    public const NAME_EVENT_CLIENT_GET = 'api_event_client_get';
    public const NAME_EVENT_CLIENT_CREATE = 'api_event_client_create';
    public const NAME_EVENT_CLIENT_UPDATE = 'api_event_client_update';
    public const NAME_EVENT_CLIENT_NAME_GET = 'api_event_client_name_get';
    public const NAME_EVENT_CLIENT_PAGE_URL_GET = 'api_event_client_page_url_get';
    public const NAME_EVENT_GUEST_GET_BY_UUID = 'api_event_guest_get_by_uuid';
    public const NAME_ADD_FEEDBACK_GUEST_BY_UUID = 'api_event_guest_add_feedback_by_uuid';
    public const NAME_ADD_IMAGE_ARCHIVE_EMAIL_GUEST_BY_UUID = 'api_event_guest_add_image_arvhive_email_by_uuid';

    #[Route('/client/page-url/{orderId}', name: self::NAME_EVENT_CLIENT_PAGE_URL_GET, methods: ['GET'])]
    public function getClientEventUrlByOrderId(
        Request           $request,
        EventFetchService $eventFetchService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $eventFetchVO = $eventFetchService->fetchByOrderId($orderId);

        return $this->json([
            'uuid' => $eventFetchVO->uuid,
        ]);
    }

    #[Route('/client', name: self::NAME_EVENT_CLIENT_CREATE, methods: ['POST'])]
    public function createClient(
        Request         $request,
        EventAddService $eventAddService,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $eventAddValueObject = new EventAddValueObject(
            email: new EmailValueObject($data['client_email']),
            orderId: new OrderIdValueObject($data['orderId']),
        );

        $event = $eventAddService->add($eventAddValueObject);

        return $this->json([
            'uuid' => $event->getUuid(),
            'orderId' => $event->getOrderId(),
            'name' => $event->getName(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/client/name/{orderId}', name: self::NAME_EVENT_CLIENT_NAME_GET, methods: ['GET'])]
    public function getClientNameByOrderId(
        Request           $request,
        EventFetchService $eventFetchService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $eventFetchVO = $eventFetchService->fetchByOrderId($orderId);

        return $this->json([
            'name' => $eventFetchVO->name,
            'font' => $eventFetchVO->nameFont,
        ]);
    }

    #[Route('/client/{orderId}', name: self::NAME_EVENT_CLIENT_UPDATE, methods: ['PATCH'])]
    public function updateClientByOrderId(
        Request            $request,
        EventUpdateService $eventUpdateService,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $event = $eventUpdateService->updateName(
            new OrderIdValueObject($request->attributes->get('orderId')),
            new EventNameValueObject($data['name']),
            new EventNameFontValueObject($data['font'])
        );

        return $this->json([
            'uuid' => $event->getUuid(),
            'orderId' => $event->getOrderId(),
            'name' => $event->getName(),
        ]);
    }

    #[Route('/client/{orderId}', name: self::NAME_EVENT_CLIENT_GET, methods: ['GET'])]
    public function getClientByOrderId(
        Request                $request,
        EventFetchService      $eventFetchService,
        EventMediaFetchService $eventDataService,
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

    #[Route('/{uuid}', name: self::NAME_EVENT_GUEST_GET_BY_UUID, methods: ['GET'])]
    public function getGuestByUuid(
        Request                $request,
        EventFetchService      $eventFetchService,
        EventMediaFetchService $eventDataService,
    ): JsonResponse
    {
        $uuid = new UuidValueObject($request->attributes->get('uuid'));

        $eventFetchVO = $eventFetchService->fetchByUuid($uuid);

        $eventDataDto = $eventDataService->fetch($eventFetchVO);

        return $this->json([
            'token' => $eventFetchVO->uuid,
            'name' => $eventFetchVO->name,
            'font' => $eventFetchVO->nameFont,
            'media' => [
                'backgroundPictureUrl' => $eventDataDto->backgroundPictureUrl,
                'pictures' => $eventDataDto->pictures,
            ],
        ]);
    }

    #[Route('/feedback/{uuid}', name: self::NAME_ADD_FEEDBACK_GUEST_BY_UUID, methods: ['POST'])]
    public function addFeedbackByUuid(
        Request            $request,
        AddFeedbackHandler $addFeedbackHandler,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $addFeedbackCommand = new AddFeedbackCommand(
            feedbackValueObject: new FeedbackValueObject($data['feedback'] ?? null),
            uuidValueObject: new UuidValueObject($request->attributes->get('uuid'))
        );

        $addFeedbackHandler($addFeedbackCommand);

        return $this->json([]);
    }

    #[Route('/image-archive/{uuid}', name: self::NAME_ADD_IMAGE_ARCHIVE_EMAIL_GUEST_BY_UUID, methods: ['POST'])]
    public function addImageArchiveEmail(
        Request                     $request,
        AddImageArchiveEmailHandler $addImageArchiveEmailHandler,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $addImageArchiveEmailCommand = new AddImageArchiveEmailCommand(
            emailValueObject: new EmailValueObject($data['email'] ?? null),
            uuidValueObject: new UuidValueObject($request->attributes->get('uuid'))
        );

        $addImageArchiveEmailHandler($addImageArchiveEmailCommand);

        return $this->json([]);
    }
}

