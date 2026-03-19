<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileUpdateProfilePictureControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testUpdateProfilePictureReturns401WithoutToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/update/profile/profile-picture/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfilePictureReturns401WithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/update/profile/profile-picture/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => 'invalid-token'
            ]
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfilePictureReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/update/profile/profile-picture/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ]
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfilePictureWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/update/profile/profile-picture/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ]
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
