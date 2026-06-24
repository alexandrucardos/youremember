<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileCreateControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testCreateProfile(): void
    {
        $orderId = rand(10000, 99999);

        $this->client->request(
            'POST',
            '/api/v2/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'order_id' => $orderId
            ])
        );

        // With super admin token, passes authentication and creates profile successfully
        self::assertResponseStatusCodeSame(201);
    }

    public function testCreateProfileReturns401WithoutToken(): void
    {
        $this->client->request('POST', '/api/v2/profile', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'client_email' => 'test@example.com',
            'order_id' => 123
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateProfileReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/profile',
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
            '/api/v2/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'order_id' => 123
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateProfileSuccessfullyWritesToDatabase(): void
    {
        $orderId = rand(1000, 9999);
        $clientEmail = 'functional-test-' . time() . '@example.com';

        $this->client->request(
            'POST',
            '/api/v2/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')
            ],
            json_encode([
                'client_email' => $clientEmail,
                'order_id' => $orderId
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

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    private function generateExpiredToken(string $email): string
    {
        $apiKey = 'test-api-key';
        $expiration = time() - 86_400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }

    private function generateValidToken(string $email): string
    {
        $apiKey = 'test-api-key';
        $expiration = time() + 86_400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }
}
