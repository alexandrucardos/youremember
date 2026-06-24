<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileInfoGetControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testGetProfileByOrderIdReturns401WithoutToken(): void
    {
        $this->client->request('GET', '/api/v2/profile/information/oderId/123');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetProfileByOrderIdReturns400WithInvalidToken(): void
    {
        $this->client->request('GET', '/api/v2/profile/information/oderId/123', [], [], ['HTTP_TOKEN' => 'x']);

        // Invalid token format returns 400
        self::assertResponseStatusCodeSame(400);
    }

    public function testGetProfileByOrderIdReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'GET',
            '/api/v2/profile/information/oderId/123',
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

        // Now retrieve the profile
        $this->client->request(
            'GET',
            '/api/v2/profile/information/oderId/' . $orderId,
            [],
            [],
            ['HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')]
        );

        self::assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertArrayHasKey('profileId', $response);
        self::assertArrayHasKey('name', $response);
        self::assertArrayHasKey('font', $response);
        self::assertEquals('classic', $response['font']);
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
