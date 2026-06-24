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

    public function testUpdateProfileNameAndFontReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'PATCH',
            '/api/v2/update/name/font/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'x'
            ],
            json_encode([
                'name' => 'Updated Profile Name',
                'font' => 'Arial'
            ])
        );

        self::assertResponseStatusCodeSame(400);
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
        // First create a profile to update
        $orderId = rand(100000, 999999);
        $clientEmail = 'name-test-' . time() . '@example.com';

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

        // Now update the name and font
        $this->client->request(
            'PATCH',
            '/api/v2/update/name/font/orderId/' . $orderId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')
            ],
            json_encode([
                'name' => 'Updated Profile Name',
                'font' => 'Arial'
            ])
        );

        self::assertResponseStatusCodeSame(200);

        // Verify the update worked by checking database
        $container = static::getContainer();
        $profileRepository = $container->get('App\Repository\ProfileRepository');
        $profile = $profileRepository->findOneBy(['order_id' => $orderId]);

        self::assertNotNull($profile);
        self::assertEquals('Updated Profile Name', $profile->getName());
        self::assertEquals('Arial', $profile->getNameFont());
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
