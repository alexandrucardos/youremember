<?php

declare(strict_types = 1);

namespace App\Controller\API;

use App\Application\InitiateMultipartMediaUpload\InitiateMediaUploadCommand;
use App\Application\InitiateMultipartMediaUpload\InitiateMediaUploadHandler;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\UserRole;
use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\Service\Media\MediaConfirmService;
use App\Service\Media\MediaCountService;
use App\Service\Media\MediaPresignService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/media/add/multipart')]
final class MediaMultipartController extends AbstractController
{
    public const NAME_MEDIA_MULTIPART_INITIATE = 'api_media_multipart_initiate_v1';
    public const NAME_MEDIA_MULTIPART_PART = 'api_media_multipart_part_v1';
    public const NAME_MEDIA_MULTIPART_COMPLETE = 'api_media_multipart_complete_v1';
    public const NAME_MEDIA_MULTIPART_ABORT = 'api_media_multipart_abort_v1';

    #[Route('/initiate/orderId/{order_id}', name: self::NAME_MEDIA_MULTIPART_INITIATE, methods: ['POST'])]
    public function guestMultipartInitiate(
        Request $request,
        InitiateMediaUploadHandler $initiateMediaUploadHandler
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException();
        }

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

    #[Route('/part/orderId/{order_id}', name: self::NAME_MEDIA_MULTIPART_PART, methods: ['POST'])]
    public function guestMultipartPart(
        Request $request,
        MediaPresignService $mediaPresignService
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException();
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

    #[Route('/complete/orderId/{order_id}', name: self::NAME_MEDIA_MULTIPART_COMPLETE, methods: ['POST'])]
    public function guestMultipartComplete(
        Request $request,
        MediaConfirmService $mediaConfirmService,
        MediaCountService $mediaCountService
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException();
        }

        $orderId = new OrderIdValueObject($request->attributes->get('order_id'));

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

        $mediaCountService->incrementByOrderId($orderId->value, 1);
        $mediaConfirmService->completeMultipartUpload(
            $orderId->value,
            $key,
            $uploadId,
            $filename,
            $mimeType,
            (int) $fileSize,
            $parts
        );

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route('/abort/orderId/{order_id}', name: self::NAME_MEDIA_MULTIPART_ABORT, methods: ['DELETE'])]
    public function guestMultipartAbort(
        Request $request,
        MediaPresignService $mediaPresignService
    ): JsonResponse {
        $userRole = $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE);

        if (!in_array($userRole, UserRole::getAdminRoles(), true)) {
            throw new AccessDeniedHttpException();
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
