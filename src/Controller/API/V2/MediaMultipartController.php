<?php

declare(strict_types = 1);

namespace App\Controller\API\V2;

use App\Application\InitiateMultipartMediaUpload\InitiateMediaUploadCommand;
use App\Application\InitiateMultipartMediaUpload\InitiateMediaUploadHandler;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\Service\Event\EventFetchService;
use App\Service\Media\MediaConfirmService;
use App\Service\Media\MediaCountService;
use App\Service\Media\MediaPresignService;
use App\ValueObject\EmailValueObject;
use App\ValueObject\HashValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\ProfileIdValueObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v2/media/add/multipart')]
final class MediaMultipartController extends AbstractController
{
    public const NAME_MEDIA_GUEST_MULTIPART_INITIATE = 'api_media_guest_multipart_initiate_v2';
    public const NAME_MEDIA_GUEST_MULTIPART_PART = 'api_media_guest_multipart_part_v2';
    public const NAME_MEDIA_GUEST_MULTIPART_COMPLETE = 'api_media_guest_multipart_complete_v2';
    public const NAME_MEDIA_GUEST_MULTIPART_ABORT = 'api_media_guest_multipart_abort_v2';

    #[Route('/initiate/order_id/{order_id}', name: self::NAME_MEDIA_GUEST_MULTIPART_INITIATE, methods: ['POST'])]
    public function guestMultipartInitiate(
        Request $request,
        InitiateMediaUploadHandler $initiateMediaUploadHandler
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $filename = $data['filename'] ?? null;
        $mimeType = $data['mimeType'] ?? null;

        if (!$filename || !$mimeType) {
            return $this->json(['error' => 'filename and mimeType are required'], Response::HTTP_BAD_REQUEST);
        }

        $command = new InitiateMediaUploadCommand(
            orderIdValueObject: new OrderIdValueObject($request->attributes->get('order_id')),
            userEmail: new EmailValueObject($request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)),
            filename: $filename,
            mimeType: $mimeType
        );

        $result = $initiateMediaUploadHandler($command);

        return $this->json($result);
    }

    #[Route('/part/orderId/{order_id}', name: self::NAME_MEDIA_GUEST_MULTIPART_PART, methods: ['POST'])]
    public function guestMultipartPart(
        Request $request,
        MediaPresignService $mediaPresignService
    ): JsonResponse {
        $hashHeader = $request->headers->get('hash');

        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $key = $data['key'] ?? null;
        $uploadId = $data['uploadId'] ?? null;
        $partNumber = $data['partNumber'] ?? null;

        if (!$key || !$uploadId || !$partNumber) {
            return $this->json(['error' => 'key, uploadId and partNumber are required'], Response::HTTP_BAD_REQUEST);
        }

        $presignedUrl = $mediaPresignService->getPresignedPartUrl($key, $uploadId, (int) $partNumber);

        return $this->json(['presignedUrl' => $presignedUrl]);
    }

    #[Route('/complete/eventUuid/{uuid}', name: self::NAME_MEDIA_GUEST_MULTIPART_COMPLETE, methods: ['POST'])]
    public function guestMultipartComplete(
        Request $request,
        MediaConfirmService $mediaConfirmService,
        MediaCountService $mediaCountService,
        EventFetchService $eventFetchService
    ): JsonResponse {
        $hashHeader = $request->headers->get('hash');
        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $hash = new HashValueObject($hashHeader);
        $uuid = new ProfileIdValueObject($request->attributes->get('uuid'));

        $eventFetchVO = $eventFetchService->fetchByUuid($uuid);

        $data = json_decode($request->getContent(), true);
        $key = $data['key'] ?? null;
        $uploadId = $data['uploadId'] ?? null;
        $filename = $data['filename'] ?? null;
        $mimeType = $data['mimeType'] ?? null;
        $fileSize = $data['fileSize'] ?? null;
        $parts = $data['parts'] ?? [];

        if (!$key || !$uploadId || !$filename || !$mimeType || $fileSize === null || empty($parts)) {
            return $this->json([
                'error' => 'key, uploadId, filename, mimeType, fileSize and parts are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $mediaCountService->incrementByOrderId($eventFetchVO['orderId'], 1);
        $mediaConfirmService->completeMultipartUpload(
            $eventFetchVO['orderId'],
            $key,
            $uploadId,
            $filename,
            $mimeType,
            (int) $fileSize,
            $hash->value,
            $parts
        );

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/abort/eventUuid/{uuid}', name: self::NAME_MEDIA_GUEST_MULTIPART_ABORT, methods: ['DELETE'])]
    public function guestMultipartAbort(
        Request $request,
        MediaPresignService $mediaPresignService
    ): JsonResponse {
        $hashHeader = $request->headers->get('hash');
        if (!$hashHeader) {
            return $this->json(['error' => 'Missing hash header'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $key = $data['key'] ?? null;
        $uploadId = $data['uploadId'] ?? null;

        if (!$key || !$uploadId) {
            return $this->json(['error' => 'key and uploadId are required'], Response::HTTP_BAD_REQUEST);
        }

        $mediaPresignService->abortMultipartUpload($key, $uploadId);

        return $this->json([]);
    }
}
