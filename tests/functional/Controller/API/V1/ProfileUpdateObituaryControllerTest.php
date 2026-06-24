<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V1;

use App\Service\FrontendTokenParserService;
use App\Tests\functional\FunctionalTestBase;

class ProfileUpdateObituaryControllerTest extends FunctionalTestBase
{
    public function testUpdateProfileObituaryReturns401WithoutToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v1/update/obituary/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['obituary' => 'Updated obituary text'])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileObituaryReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v1/update/obituary/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'x'
            ],
            json_encode(['obituary' => 'Updated obituary text'])
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testUpdateProfileObituaryReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v1/update/obituary/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ],
            json_encode(['obituary' => 'Updated obituary text'])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileObituaryWithValidTokenPassesAuthentication(): void
    {
        // First create a profile to update
        $orderId = rand(100000, 999999);
        $clientEmail = 'obituary-test-' . time() . '@example.com';

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

        // Now update the obituary
        $this->client->request(
            'PATCH',
            '/api/v1/update/obituary/orderId/' . $orderId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')
            ],
            json_encode(['obituary' => 'Updated obituary text'])
        );

        self::assertResponseStatusCodeSame(200);

        // Verify the update worked by checking database
        $container = static::getContainer();
        $profileRepository = $container->get('App\Repository\ProfileRepository');
        $profile = $profileRepository->findOneBy(['order_id' => $orderId]);

        self::assertNotNull($profile);
        self::assertEquals('Updated obituary text', $profile->getObituary());
    }
}
