<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V1;

use App\Service\FrontendTokenParserService;
use App\Tests\functional\FunctionalTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileUpdateProfilePictureControllerTest extends FunctionalTestBase
{
    public function testUpdateProfilePictureReturns401WithoutToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/update/profile/profile-picture/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfilePictureReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/update/profile/profile-picture/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => 'x'
            ]
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testUpdateProfilePictureReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/update/profile/profile-picture/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ]
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfilePictureWithValidTokenPassesAuthentication(): void
    {
        $clientEmail = 'customer@email.ro';

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
                'order_id' => 1,
                'profile_id' => 1
            ])
        );

        $this->client->request(
            'POST',
            '/api/v1/update/profile/profile-picture/orderId/1',
            [],
            [
                'file' => new UploadedFile(path: __DIR__ . '/../../../assets/profile.jpg', originalName: 'profile.jpg')
            ],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => $this->generateValidToken($clientEmail)
            ]
        );

        self::assertResponseStatusCodeSame(200);
    }
}
