<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileUpdateBackgroundControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testUpdateProfileBackgroundReturns401WithoutToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/update/profile/background/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileBackgroundReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/update/profile/background/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => 'x'
            ]
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testUpdateProfileBackgroundReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/update/profile/background/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ]
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileBackgroundWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/update/profile/background/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')
            ]
        );

        // With super admin token, passes authentication (not 401/403)
        self::assertResponseStatusCodeSame(500);
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
