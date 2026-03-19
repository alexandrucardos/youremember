<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileCreateControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testCreateProfileReturns401WithoutToken(): void
    {
        $this->client->request('POST', '/api/v2/profile', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'client_email' => 'test@example.com',
            'order_id' => 123
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateProfileReturns401WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'invalid-token'
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'order_id' => 123
            ])
        );

        self::assertResponseStatusCodeSame(401);
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

    public function testCreateProfileWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode([
                'client_email' => 'test@example.com',
                'order_id' => 123
            ])
        );

        // With valid token, we get past authentication (not 401)
        // Will fail with 404 (user not found) or 500 (db issue) but NOT 401
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
