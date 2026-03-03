<?php

namespace App\Controller\API;

use App\Service\Event\EventFetchService;
use App\Service\Event\EventMediaFetchService;
use App\Service\Media\MediaConfirmService;
use App\Service\Media\MediaCountService;
use App\Service\Media\MediaDeleteService;
use App\Service\Media\MediaPresignService;
use App\Service\Media\MediaService;
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
    public const NAME_MEDIA_CLIENT_BACKGROUND_ADD = 'api_media_client_background_add';
    public const NAME_MEDIA_CLIENT_BACKGROUND_GET = 'api_media_client_background_get';
    public const NAME_MEDIA_CLIENT_GET = 'api_media_client_get';
    public const NAME_MEDIA_CLIENT_ADD = 'api_media_client_add';
    public const NAME_MEDIA_CLIENT_DELETE = 'api_media_client_delete';
    public const NAME_MEDIA_CLIENT_MULTIPART_INITIATE = 'api_media_client_multipart_initiate';
    public const NAME_MEDIA_CLIENT_MULTIPART_PART = 'api_media_client_multipart_part';
    public const NAME_MEDIA_CLIENT_MULTIPART_COMPLETE = 'api_media_client_multipart_complete';
    public const NAME_MEDIA_CLIENT_MULTIPART_ABORT = 'api_media_client_multipart_abort';
    public const NAME_MEDIA_GUEST_ADD = 'api_media_guest_add';
    public const NAME_MEDIA_GUEST_DELETE = 'api_media_guest_delete';
    public const NAME_MEDIA_GUEST_MULTIPART_INITIATE = 'api_media_guest_multipart_initiate';
    public const NAME_MEDIA_GUEST_MULTIPART_PART = 'api_media_guest_multipart_part';
    public const NAME_MEDIA_GUEST_MULTIPART_COMPLETE = 'api_media_guest_multipart_complete';
    public const NAME_MEDIA_GUEST_MULTIPART_ABORT = 'api_media_guest_multipart_abort';

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
        MediaService             $mediatorS3Service,
        EventDispatcherInterface $eventDispatcher,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $file = $request->files->get('file', '');

        if (empty($file)) {
            return $this->json(['error' => 'No files uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $mediatorS3Service->uploadBackground($orderId->value, $file);

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/client/{orderId}', name: self::NAME_MEDIA_CLIENT_GET, methods: ['GET'])]
    public function clientGet(
        Request                $request,
        EventFetchService      $eventFetchService,
        EventMediaFetchService $eventDataService,
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
        Request           $request,
        MediaService      $mediatorS3Service,
        MediaCountService $mediaCountService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $files = $request->files->get('files', []);

        if (empty($files)) {
            return $this->json(['error' => 'No files uploaded'], Response::HTTP_BAD_REQUEST);
        }
        $mediaCountService->incrementByOrderId($orderId->value, count($files));

        $mediatorS3Service->uploadMultiple($orderId->value, $files);

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/client', name: self::NAME_MEDIA_CLIENT_DELETE, methods: ['DELETE'])]
    public function clientDelete(
        Request            $request,
        MediaDeleteService $mediaDeleteService,
        MediaCountService  $mediaCountService,
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
            $mediaDeleteService->deleteByUrl($url);
        }

        return $this->json(['deleted' => true]);
    }

    #[Route('/guest/{uuid}', name: self::NAME_MEDIA_GUEST_ADD, methods: ['POST'])]
    public function guestAdd(
        Request           $request,
        MediaService      $mediatorS3Service,
        EventFetchService $eventFetchService,
        MediaCountService $mediaCountService,
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

        $mediatorS3Service->uploadMultiple($eventFetchVO->orderId, $files, $hash->value);

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/guest', name: self::NAME_MEDIA_GUEST_DELETE, methods: ['DELETE'])]
    public function guestDelete(
        Request            $request,
        MediaDeleteService $mediaDeleteService,
        MediaCountService  $mediaCountService,
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
        $mediaDeleteService->deleteContent($url, $hash);

        return $this->json(['deleted' => true]);
    }

    #[Route('/client/{orderId}/multipart/initiate', name: self::NAME_MEDIA_CLIENT_MULTIPART_INITIATE, methods: ['POST'])]
    public function clientMultipartInitiate(
        Request             $request,
        MediaPresignService $mediaPresignService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $data = json_decode($request->getContent(), true);
        $filename = $data['filename'] ?? null;
        $mimeType = $data['mimeType'] ?? null;

        if (!$filename || !$mimeType) {
            return $this->json(['error' => 'filename and mimeType are required'], Response::HTTP_BAD_REQUEST);
        }

        $result = $mediaPresignService->initiateMultipartUpload($orderId->value, $filename, $mimeType, MediaService::FOLDER_CLIENT);

        return $this->json($result);
    }

    #[Route('/client/{orderId}/multipart/part', name: self::NAME_MEDIA_CLIENT_MULTIPART_PART, methods: ['POST'])]
    public function clientMultipartPart(
        Request             $request,
        MediaPresignService $mediaPresignService,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $key        = $data['key'] ?? null;
        $uploadId   = $data['uploadId'] ?? null;
        $partNumber = $data['partNumber'] ?? null;

        if (!$key || !$uploadId || !$partNumber) {
            return $this->json(['error' => 'key, uploadId and partNumber are required'], Response::HTTP_BAD_REQUEST);
        }

        $presignedUrl = $mediaPresignService->getPresignedPartUrl($key, $uploadId, (int) $partNumber);

        return $this->json(['presignedUrl' => $presignedUrl]);
    }

    #[Route('/client/{orderId}/multipart/complete', name: self::NAME_MEDIA_CLIENT_MULTIPART_COMPLETE, methods: ['POST'])]
    public function clientMultipartComplete(
        Request             $request,
        MediaConfirmService $mediaConfirmService,
        MediaCountService   $mediaCountService,
    ): JsonResponse
    {
        $orderId = new OrderIdValueObject($request->attributes->get('orderId'));

        $data     = json_decode($request->getContent(), true);
        $key      = $data['key'] ?? null;
        $uploadId = $data['uploadId'] ?? null;
        $filename = $data['filename'] ?? null;
        $mimeType = $data['mimeType'] ?? null;
        $fileSize = $data['fileSize'] ?? null;
        $parts    = $data['parts'] ?? [];

        if (!$key || !$uploadId || !$filename || !$mimeType || $fileSize === null || empty($parts)) {
            return $this->json(['error' => 'key, uploadId, filename, mimeType, fileSize and parts are required'], Response::HTTP_BAD_REQUEST);
        }

        $mediaCountService->incrementByOrderId($orderId->value, 1);
        $mediaConfirmService->completeMultipartUpload($orderId->value, $key, $uploadId, $filename, $mimeType, (int) $fileSize, MediaService::FOLDER_CLIENT, $parts);

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/client/{orderId}/multipart/abort', name: self::NAME_MEDIA_CLIENT_MULTIPART_ABORT, methods: ['DELETE'])]
    public function clientMultipartAbort(
        Request             $request,
        MediaPresignService $mediaPresignService,
    ): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $key      = $data['key'] ?? null;
        $uploadId = $data['uploadId'] ?? null;

        if (!$key || !$uploadId) {
            return $this->json(['error' => 'key and uploadId are required'], Response::HTTP_BAD_REQUEST);
        }

        $mediaPresignService->abortMultipartUpload($key, $uploadId);

        return $this->json([]);
    }

    #[Route('/guest/{uuid}/multipart/initiate', name: self::NAME_MEDIA_GUEST_MULTIPART_INITIATE, methods: ['POST'])]
    public function guestMultipartInitiate(
        Request             $request,
        MediaPresignService $mediaPresignService,
        EventFetchService   $eventFetchService,
    ): JsonResponse
    {
        $hashHeader = $request->headers->get('hash');
        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $hash = new HashValueObject($hashHeader);
        $uuid = new UuidValueObject($request->attributes->get('uuid'));

        $eventFetchVO = $eventFetchService->fetchByUuid($uuid);

        $data = json_decode($request->getContent(), true);
        $filename = $data['filename'] ?? null;
        $mimeType = $data['mimeType'] ?? null;

        if (!$filename || !$mimeType) {
            return $this->json(['error' => 'filename and mimeType are required'], Response::HTTP_BAD_REQUEST);
        }

        $result = $mediaPresignService->initiateMultipartUpload($eventFetchVO->orderId, $filename, $mimeType, $hash->value);

        return $this->json($result);
    }

    #[Route('/guest/{uuid}/multipart/part', name: self::NAME_MEDIA_GUEST_MULTIPART_PART, methods: ['POST'])]
    public function guestMultipartPart(
        Request             $request,
        MediaPresignService $mediaPresignService,
    ): JsonResponse
    {
        $hashHeader = $request->headers->get('hash');
        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $data       = json_decode($request->getContent(), true);
        $key        = $data['key'] ?? null;
        $uploadId   = $data['uploadId'] ?? null;
        $partNumber = $data['partNumber'] ?? null;

        if (!$key || !$uploadId || !$partNumber) {
            return $this->json(['error' => 'key, uploadId and partNumber are required'], Response::HTTP_BAD_REQUEST);
        }

        $presignedUrl = $mediaPresignService->getPresignedPartUrl($key, $uploadId, (int) $partNumber);

        return $this->json(['presignedUrl' => $presignedUrl]);
    }

    #[Route('/guest/{uuid}/multipart/complete', name: self::NAME_MEDIA_GUEST_MULTIPART_COMPLETE, methods: ['POST'])]
    public function guestMultipartComplete(
        Request             $request,
        MediaConfirmService $mediaConfirmService,
        MediaCountService   $mediaCountService,
        EventFetchService   $eventFetchService,
    ): JsonResponse
    {
        $hashHeader = $request->headers->get('hash');
        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $hash = new HashValueObject($hashHeader);
        $uuid = new UuidValueObject($request->attributes->get('uuid'));

        $eventFetchVO = $eventFetchService->fetchByUuid($uuid);

        $data     = json_decode($request->getContent(), true);
        $key      = $data['key'] ?? null;
        $uploadId = $data['uploadId'] ?? null;
        $filename = $data['filename'] ?? null;
        $mimeType = $data['mimeType'] ?? null;
        $fileSize = $data['fileSize'] ?? null;
        $parts    = $data['parts'] ?? [];

        if (!$key || !$uploadId || !$filename || !$mimeType || $fileSize === null || empty($parts)) {
            return $this->json(['error' => 'key, uploadId, filename, mimeType, fileSize and parts are required'], Response::HTTP_BAD_REQUEST);
        }

        $mediaCountService->incrementByOrderId($eventFetchVO->orderId, 1);
        $mediaConfirmService->completeMultipartUpload($eventFetchVO->orderId, $key, $uploadId, $filename, $mimeType, (int) $fileSize, $hash->value, $parts);

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/guest/{uuid}/multipart/abort', name: self::NAME_MEDIA_GUEST_MULTIPART_ABORT, methods: ['DELETE'])]
    public function guestMultipartAbort(
        Request             $request,
        MediaPresignService $mediaPresignService,
    ): JsonResponse
    {
        $hashHeader = $request->headers->get('hash');
        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $data     = json_decode($request->getContent(), true);
        $key      = $data['key'] ?? null;
        $uploadId = $data['uploadId'] ?? null;

        if (!$key || !$uploadId) {
            return $this->json(['error' => 'key and uploadId are required'], Response::HTTP_BAD_REQUEST);
        }

        $mediaPresignService->abortMultipartUpload($key, $uploadId);

        return $this->json([]);
    }
}