<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V1;

use App\Service\FrontendTokenParserService;
use App\Tests\functional\FunctionalTestBase;

class ProfileCreateControllerTest extends FunctionalTestBase
{
    public function testCreateProfileReturns401WithoutToken(): void
    {
        $this->client->request('POST', '/api/v1/profile', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'client_email' => 'test@example.com',
            'order_id' => 123
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateProfileReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'x'
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'order_id' => 123
            ])
        );

        // Invalid token format returns 400
        self::assertResponseStatusCodeSame(400);
    }

    public function testCreateProfileReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateExpiredToken(FrontendTokenParserService::SUPER_ADMIN_EMAIL)
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'order_id' => 123
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateProfile(): void
    {
        $orderId = rand(1000, 9999);
        $clientEmail = 'functional-test-' . time() . '@example.com';

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

        $container = static::getContainer();
        $profileRepository = $container->get('App\Repository\ProfileRepository');

        $profile = $profileRepository->findOneBy(['order_id' => $orderId]);

        self::assertNotNull($profile, 'Profile should be persisted to database');
        self::assertEquals($orderId, $profile->getOrderId());
        self::assertNotNull($profile->getExternalId());
        self::assertEquals('classic', $profile->getNameFont());
        self::assertNotNull($profile->getUser());
        self::assertEquals($clientEmail, $profile->getUser()->getEmail());
    }
}
