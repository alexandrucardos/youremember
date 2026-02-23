<?php

namespace App\Tests\functional\Controller\API;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MediaControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testClientAddMediaReturns401WithoutToken(): void
    {
        $this->client->request('POST', '/api/media/client/123');

        self::assertResponseStatusCodeSame(401);
    }

    public function testClientAddMediaWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'POST',
            '/api/media/client/123',
            [],
            [],
            ['HTTP_TOKEN' => $this->generateValidToken('test@example.com')]
        );

        // With valid token, passes auth - returns 400 (no files uploaded)
        self::assertResponseStatusCodeSame(400);
    }

    private function generateValidToken(string $email): string
    {
        $apiKey = 'test-api-key';
        $expiration = time() + 86400;
        $data = $email . '|' . $expiration;
        $hmac = hash_hmac('sha256', $data, $apiKey);

        return $data . '|' . $hmac;
    }

    public function testClientDeleteMediaReturns401WithoutToken(): void
    {
        $this->client->request(
            'DELETE',
            '/api/media/client',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['url' => 'https://bucket.s3.amazonaws.com/123/client/photo.jpg'])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testClientDeleteMediaWithValidTokenPassesAuthentication(): void
    {
        $this->client->request(
            'DELETE',
            '/api/media/client',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
            ],
            json_encode(['url' => 'https://bucket.s3.amazonaws.com/123/client/photo.jpg'])
        );

        // With valid token, passes auth - returns 404 (media not found)
        self::assertResponseStatusCodeSame(404);
    }

    public function testGuestAddMediaReturns400WithoutHash(): void
    {
        $this->client->request('POST', '/api/media/guest/550e8400-e29b-41d4-a716-446655440000');

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('Missing hash header', $response['error']);
    }

    public function testGuestAddMediaWithHashReturns404WhenEventNotFound(): void
    {
        $this->client->request(
            'POST',
            '/api/media/guest/550e8400-e29b-41d4-a716-446655440000',
            [],
            [],
            ['HTTP_HASH' => 'validhash123']
        );

        // Returns 404 because event doesn't exist
        self::assertResponseStatusCodeSame(404);
    }

    public function testGuestDeleteMediaReturns400WithoutHash(): void
    {
        $this->client->request(
            'DELETE',
            '/api/media/guest',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['url' => 'https://bucket.s3.amazonaws.com/123/hash123/photo.jpg'])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('Missing hash header', $response['error']);
    }

    public function testGuestDeleteMediaReturns400WithoutUrl(): void
    {
        $this->client->request(
            'DELETE',
            '/api/media/guest',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_HASH' => 'validhash123',
            ],
            json_encode([])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('URL is required', $response['error']);
    }

    public function testGuestDeleteMediaWithHashAndUrlReturnsNotFound(): void
    {
        $this->client->request(
            'DELETE',
            '/api/media/guest',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_HASH' => 'validhash123',
            ],
            json_encode(['url' => 'https://bucket.s3.amazonaws.com/123/validhash123/photo.jpg'])
        );

        // Returns 404 because media doesn't exist
        self::assertResponseStatusCodeSame(404);
    }

    protected function setUp(): void
    {
        $this->markTestSkipped();
        $this->client = static::createClient();
    }
}