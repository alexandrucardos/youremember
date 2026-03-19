<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileUpdateObituaryControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testUpdateProfileObituaryReturns401WithoutToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v2/update/obituary/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['obituary' => 'Updated obituary text'])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileObituaryReturns401WithInvalidToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v2/update/obituary/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'invalid-token'
            ],
            json_encode(['obituary' => 'Updated obituary text'])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileObituaryReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v2/update/obituary/orderId/123',
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
        $this->client->request(
            'PATCH',
            '/api/v2/update/obituary/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode(['obituary' => 'Updated obituary text'])
        );

        // With valid token, we get past authentication (not 401)
        self::assertResponseStatusCodeSame(404);
    }

    protected function setUp(): void
    {
        $this->markTestSkipped();
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
