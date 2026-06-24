<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V1;

use App\Service\FrontendTokenParserService;
use App\Tests\functional\FunctionalTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileUpdateBackgroundControllerTest extends FunctionalTestBase
{
    public function testUpdateProfileBackgroundReturns401WithoutToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/update/profile/background/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileBackgroundReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/update/profile/background/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => 'x'
            ]
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testUpdateProfileBackgroundReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/update/profile/background/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ]
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileBackgroundWithValidTokenPassesAuthentication(): void
    {
        $orderId = rand(100000, 999999);
        $clientEmail = 'name-test-' . time() . '@example.com';

        $this->client->request(
            'POST',
            '/api/v1/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken(FrontendTokenParserService::SUPER_ADMIN_EMAIL)
            ],
            json_encode([
                'client_email' => $clientEmail,
                'order_id' => $orderId,
                'profile_id' => 1
            ])
        );

        self::assertResponseStatusCodeSame(201);

        $this->client->request(
            'POST',
            "/api/v1/update/profile/background/orderId/$orderId",
            [],
            [
                'file' => new UploadedFile(
                    path: __DIR__ . '/../../../assets/background.jpg',
                    originalName: 'background.jpg'
                )
            ],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => $this->generateValidToken('client@email.ro')
            ]
        );

        self::assertResponseStatusCodeSame(200);
    }
}
