<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testCreateUserReturns401WithoutToken(): void
    {
        $this->client->request('POST', '/api/user/client', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'newuser@example.com'
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateUserReturns401WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/user/client',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'invalid-token'
            ],
            json_encode([
                'email' => 'newuser@example.com'
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateUserReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'POST',
            '/api/user/client',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateExpiredToken('admin@example.com')
            ],
            json_encode([
                'email' => 'newuser@example.com'
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    private function generateExpiredToken(string $email): string
    {
        $apiKey = 'test-api-key';
        $expiration = time() - 86_400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }

    public function testCreateUserWithValidTokenReturns201(): void
    {
        $this->client->request(
            'POST',
            '/api/user/client',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin@example.com')
            ],
            json_encode([
                'email' => 'newuser@example.com'
            ])
        );

        self::assertResponseStatusCodeSame(201);
    }

    private function generateValidToken(string $email): string
    {
        $apiKey = 'test-api-key';
        $expiration = time() + 86_400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }

    public function testCreateUserWithValidTokenReturns400WhenEmailIsNull(): void
    {
        $this->client->request(
            'POST',
            '/api/user/client',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin@example.com')
            ],
            json_encode([
                'email' => null
            ])
        );

        // Email value object rejects null - returns 400
        self::assertResponseStatusCodeSame(400);
    }

    protected function setUp(): void
    {
        $this->markTestSkipped();
        $this->client = static::createClient();
    }
}
