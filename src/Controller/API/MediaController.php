<?php

namespace App\Controller\API;

use App\Service\MediatorS3Service;
use App\ValueObject\OrderIdValueObject;
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

        $deleted = $mediatorS3Service->deleteContent($url);

        if (!$deleted) {
            return $this->json(['error' => 'Failed to delete media'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['deleted' => true]);
    }
}