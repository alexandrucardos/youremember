<?php

namespace App\Controller\API;

use App\Event\MediaUploadedEvent;
use App\Service\Event\EventDataService;
use App\Service\Event\EventFetchService;
use App\Service\Media\MediaCountService;
use App\Service\MediatorS3Service;
use App\ValueObject\HashValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/media')]
final class MediaController extends AbstractController
{
    public const NAME_MEDIA_CLIENT_ADD = 'api_media_client_add';
    public const NAME_MEDIA_CLIENT_BACKGROUND_ADD = 'api_media_client_background_add';
    public const NAME_MEDIA_CLIENT_BACKGROUND_GET = 'api_media_client_background_get';
    public const API_MEDIA_CLIENT_GET = 'api_media_client_get';
    public const NAME_MEDIA_CLIENT_DELETE = 'api_media_client_delete';
    public const NAME_MEDIA_GUEST_ADD = 'api_media_guest_add';
    public const NAME_MEDIA_GUEST_DELETE = 'api_media_guest_delete';

    #[Route('/client/background/{orderId}', name: self::NAME_MEDIA_CLIENT_BACKGROUND_GET, methods: ['GET'])]
    public function clientBackgroundGet(
        Request           $request,
        EventFetchService $eventFetchService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $eventFetchVO = $eventFetchService->fetchByOrderId($orderId);

        return $this->json([
            'url' => $eventFetchVO->backgroundImage,
        ]);
    }

    #[Route('/client/background/{orderId}', name: self::NAME_MEDIA_CLIENT_BACKGROUND_ADD, methods: ['POST'])]
    public function clientBackgroundAdd(
        Request                  $request,
        MediatorS3Service        $mediatorS3Service,
        EventDispatcherInterface $eventDispatcher,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $file = $request->files->get('file', '');

        if (empty($file)) {
            return $this->json(['error' => 'No files uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $url = $mediatorS3Service->uploadSingle($orderId->value, $file);

        $eventDispatcher->dispatch(new MediaUploadedEvent($orderId->value));

        return $this->json([
            'url' => $url,
        ], Response::HTTP_CREATED);
    }

    #[Route('/client/{orderId}', name: self::API_MEDIA_CLIENT_GET, methods: ['GET'])]
    public function clientGet(
        Request           $request,
        EventFetchService $eventFetchService,
        EventDataService  $eventDataService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $eventFetchVO = $eventFetchService->fetchByOrderId($orderId);

        $eventDataDto = $eventDataService->fetch($eventFetchVO);

        return $this->json([
            'pictures' => $eventDataDto->pictures,
        ]);
    }

    #[Route('/client/{orderId}', name: self::NAME_MEDIA_CLIENT_ADD, methods: ['POST'])]
    public function clientAdd(
        Request                  $request,
        MediatorS3Service        $mediatorS3Service,
        MediaCountService        $mediaCountService,
        EventDispatcherInterface $eventDispatcher,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $files = $request->files->get('files', []);

        if (empty($files)) {
            return $this->json(['error' => 'No files uploaded'], Response::HTTP_BAD_REQUEST);
        }
        $mediaCountService->incrementByOrderId($orderId->value, count($files));

        $mediatorS3Service->uploadMultiple($orderId->value, $files);

        $eventDispatcher->dispatch(new MediaUploadedEvent($orderId->value));

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/client', name: self::NAME_MEDIA_CLIENT_DELETE, methods: ['DELETE'])]
    public function clientDelete(
        Request           $request,
        MediatorS3Service $mediatorS3Service,
        MediaCountService $mediaCountService,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $urls = $data['urls'] ?? [];

        if (empty($urls)) {
            return $this->json(['error' => 'URLs are required'], Response::HTTP_BAD_REQUEST);
        }

        //todo do this in batch
        foreach ($urls as $url) {
            $mediaCountService->decrementByUrl($url);
            $mediatorS3Service->deleteByUrl($url);
        }

        return $this->json(['deleted' => true]);
    }

    #[Route('/guest/{uuid}', name: self::NAME_MEDIA_GUEST_ADD, methods: ['POST'])]
    public function guestAdd(
        Request                  $request,
        MediatorS3Service        $mediatorS3Service,
        EventFetchService        $eventFetchService,
        MediaCountService        $mediaCountService,
        EventDispatcherInterface $eventDispatcher,
    ): JsonResponse
    {
        $hashHeader = $request->headers->get('hash');
        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $hash = new HashValueObject($hashHeader);
        $uuid = new UuidValueObject($request->attributes->get('uuid'));

        $eventFetchVO = $eventFetchService->fetchByUuid($uuid);

        $files = $request->files->get('files', []);

        if (empty($files)) {
            return $this->json(['error' => 'No files uploaded'], Response::HTTP_BAD_REQUEST);
        }
        $mediaCountService->incrementByOrderId($eventFetchVO->orderId, count($files));

        $url = $mediatorS3Service->uploadMultiple($eventFetchVO->orderId, $files, $hash->value);

        $eventDispatcher->dispatch(new MediaUploadedEvent($eventFetchVO->orderId));

        return $this->json([
            'url' => $url,
        ], Response::HTTP_CREATED);
    }

    #[Route('/guest', name: self::NAME_MEDIA_GUEST_DELETE, methods: ['DELETE'])]
    public function guestDelete(
        Request           $request,
        MediatorS3Service $mediatorS3Service,
        MediaCountService $mediaCountService,
    ): JsonResponse
    {
        $hashHeader = $request->headers->get('hash');
        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $hash = new HashValueObject($hashHeader);

        $data = json_decode($request->getContent(), true);

        $url = $data['url'] ?? null;

        if (!$url) {
            return $this->json(['error' => 'URL is required'], Response::HTTP_BAD_REQUEST);
        }

        $mediaCountService->decrementByUrl($url);
        $mediatorS3Service->deleteContent($url, $hash);

        return $this->json(['deleted' => true]);
    }
}