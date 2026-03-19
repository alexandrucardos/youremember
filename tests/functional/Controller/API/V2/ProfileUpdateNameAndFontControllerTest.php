<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileUpdateNameAndFontControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testUpdateProfileNameAndFontReturns401WithoutToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v2/update/name/font/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated Profile Name',
                'font' => 'Arial'
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileNameAndFontReturns401WithInvalidToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v2/update/name/font/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'invalid-token'
            ],
            json_encode([
                'name' => 'Updated Profile Name',
                'font' => 'Arial'
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileNameAndFontReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v2/update/name/font/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ],
            json_encode([
                'name' => 'Updated Profile Name',
                'font' => 'Arial'
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileNameAndFontWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v2/update/name/font/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode([
                'name' => 'Updated Profile Name',
                'font' => 'Arial'
            ])
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
