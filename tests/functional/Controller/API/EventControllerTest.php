<?php

namespace App\Tests\functional\Controller\API;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testCreateEventReturns401WithoutToken(): void
    {
        $this->client->request(
            'POST',
            '/api/event/client',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'client_email' => 'test@example.com',
                'orderId' => 123,
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateEventReturns401WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/event/client',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'invalid-token',
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'orderId' => 123,
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateEventReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'POST',
            '/api/event/client',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com'),
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'orderId' => 123,
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    private function generateExpiredToken(string $email): string
    {
        $apiKey = 'test-api-key';
        $expiration = time() - 86400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }

    public function testCreateEventWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'POST',
            '/api/event/client',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'orderId' => 123,
            ])
        );

        // With valid token, we get past authentication (not 401)
        // Will fail with 404 (user not found) or 500 (db issue) but NOT 401
        self::assertResponseStatusCodeSame(404);
    }

    private function generateValidToken(string $email): string
    {
        $apiKey = 'test-api-key';
        $expiration = time() + 86400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }

    public function testGetEventByOrderIdReturns401WithoutToken(): void
    {
        $this->client->request('GET', '/api/event/client/123');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetEventByOrderIdWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'GET',
            '/api/event/client/123',
            [],
            [],
            ['HTTP_TOKEN' => $this->generateValidToken('test@example.com')]
        );

        // With valid token, we get past authentication (not 401)
        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdateEventReturns401WithoutToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/event/client/123',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated Event Name'])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateEventWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'PATCH',
            '/api/event/client/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
            ],
            json_encode(['name' => 'Updated Event Name'])
        );

        // With valid token, we get past authentication (not 401)
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetEventByUuidReturns400ForInvalidUuid(): void
    {
        $this->client->request('GET', '/api/event/invalid-uuid');

        self::assertResponseStatusCodeSame(400);
    }

    protected function setUp(): void
    {
        $this->markTestSkipped();
        $this->client = static::createClient();
    }
}