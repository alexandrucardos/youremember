<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V1;

use App\Service\FrontendTokenParserService;
use App\Tests\functional\FunctionalTestBase;

class ProfileInfoGetControllerTest extends FunctionalTestBase
{
    public function testGetProfileByOrderIdReturns401WithoutToken(): void
    {
        $this->client->request('GET', '/api/v1/profile/information/oderId/123');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetProfileByOrderIdReturns400WithInvalidToken(): void
    {
        $this->client->request('GET', '/api/v1/profile/information/oderId/123', [], [], ['HTTP_TOKEN' => 'x']);

        // Invalid token format returns 400
        self::assertResponseStatusCodeSame(400);
    }

    public function testGetProfileByOrderIdReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'GET',
            '/api/v1/profile/information/oderId/123',
            [],
            [],
            ['HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')]
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetProfileByOrderIdWithValidTokenPassesAuthentication(): void
    {
        // First create a profile to retrieve
        $orderId = rand(100000, 999999);
        $clientEmail = 'info-test-' . time() . '@example.com';

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
            'GET',
            '/api/v1/profile/information/oderId/' . $orderId,
            [],
            [],
            ['HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')]
        );

        self::assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $profileInfo = $response['profile'];

        self::assertArrayHasKey('id', $profileInfo);
        self::assertArrayHasKey('name', $profileInfo);
        self::assertArrayHasKey('name_font', $profileInfo);
        self::assertArrayHasKey('born_at', $profileInfo);
        self::assertArrayHasKey('deceased_at', $profileInfo);
        self::assertArrayHasKey('obituary', $profileInfo);
        self::assertEquals('classic', $profileInfo['name_font']);
        self::assertEquals(1, $profileInfo['id']);
    }
}
