<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V1;

use App\Service\FrontendTokenParserService;
use App\Tests\functional\FunctionalTestBase;

class ProfileUpdateDatesControllerTest extends FunctionalTestBase
{
    public function testUpdateProfileDatesReturns401WithoutToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v1/update/dates/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'born_at' => '1980-01-01',
                'departed_at' => '2020-01-01'
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileDatesReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v1/update/dates/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'x'
            ],
            json_encode([
                'born_at' => '1980-01-01',
                'departed_at' => '2020-01-01'
            ])
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testUpdateProfileDatesReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v1/update/dates/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ],
            json_encode([
                'born_at' => '1980-01-01',
                'departed_at' => '2020-01-01'
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileDatesWithValidTokenPassesAuthentication(): void
    {
        // First create a profile to update
        $orderId = rand(100000, 999999);
        $clientEmail = 'dates-test-' . time() . '@example.com';

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

        // Now update the dates
        $this->client->request(
            'PATCH',
            '/api/v1/update/dates/orderId/' . $orderId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')
            ],
            json_encode([
                'born_at' => '1980-01-01',
                'departed_at' => '2020-01-01'
            ])
        );

        self::assertResponseStatusCodeSame(200);

        // Verify the update worked by checking database
        $container = static::getContainer();
        $profileRepository = $container->get('App\Repository\ProfileRepository');
        $profile = $profileRepository->findOneBy(['order_id' => $orderId]);

        self::assertNotNull($profile);
        self::assertEquals('1980-01-01', $profile->getBornAt()->format('Y-m-d'));
        self::assertEquals('2020-01-01', $profile->getDepartedAt()->format('Y-m-d'));
    }
}
