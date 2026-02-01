<?php

namespace App\Controller\API;

use App\Service\Event\EventFetchService;
use App\Service\MediatorS3Service;
use App\ValueObject\HashValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/media')]
final class MediaController extends AbstractController
{
    public const NAME_MEDIA_CLIENT_ADD = 'api_media_client_add';
    public const NAME_MEDIA_CLIENT_DELETE = 'api_media_client_delete';
    public const NAME_MEDIA_GUEST_ADD = 'api_media_guest_add';
    public const NAME_MEDIA_GUEST_DELETE = 'api_media_guest_delete';

    #[Route('/client/{orderId}', name: self::NAME_MEDIA_CLIENT_ADD, methods: ['POST'])]
    public function add(
        Request           $request,
        MediatorS3Service $mediatorS3Service,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $files = $request->files->get('files', []);

        if (empty($files)) {
            return $this->json(['error' => 'No files uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $url = $mediatorS3Service->uploadMultiple($orderId->value, $files);

        return $this->json([
            'url' => $url,
        ], Response::HTTP_CREATED);
    }

    #[Route('/client', name: self::NAME_MEDIA_CLIENT_DELETE, methods: ['DELETE'])]
    public function delete(
        Request           $request,
        MediatorS3Service $mediatorS3Service,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $url = $data['url'] ?? null;

        if (!$url) {
            return $this->json(['error' => 'URL is required'], Response::HTTP_BAD_REQUEST);
        }

        $mediatorS3Service->deleteByUrl($url);

        return $this->json(['deleted' => true]);
    }

    #[Route('/guest/{uuid}', name: self::NAME_MEDIA_GUEST_ADD, methods: ['POST'])]
    public function guestAdd(
        Request           $request,
        MediatorS3Service $mediatorS3Service,
        EventFetchService $eventFetchService,
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

        $url = $mediatorS3Service->uploadMultiple($eventFetchVO->orderId, $files, $hash->value);

        return $this->json([
            'url' => $url,
        ], Response::HTTP_CREATED);
    }

    #[Route('/guest', name: self::NAME_MEDIA_GUEST_DELETE, methods: ['DELETE'])]
    public function guestDelete(
        Request           $request,
        MediatorS3Service $mediatorS3Service,
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

        $mediatorS3Service->deleteContent($url, $hash);

        return $this->json(['deleted' => true]);
    }
}