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

    public function testGetProfileByOrderIdReturns401WithInvalidToken(): void
    {
        $this->client->request(
            'GET',
            '/api/v2/profile/information/oderId/123',
            [],
            [],
            ['HTTP_TOKEN' => 'invalid-token']
        );

        self::assertResponseStatusCodeSame(401);
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
        $this->client->request(
            'GET',
            '/api/v2/profile/information/oderId/123',
            [],
            [],
            ['HTTP_TOKEN' => $this->generateValidToken('test@example.com')]
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
