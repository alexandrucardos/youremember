<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use App\Repository\MediaRepository;
use App\Repository\ProfileRepository;
use App\Service\Bucket\BucketProviderInterface;
use App\Service\Bucket\MockS3ProviderService;
use App\Tests\functional\FunctionalTestBase;

class MediaMultipartControllerTest extends FunctionalTestBase
{
    private ProfileRepository $profileRepository;
    private MediaRepository $mediaRepository;
    private MockS3ProviderService $mockS3Provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profileRepository = static::getContainer()->get(ProfileRepository::class);
        $this->mediaRepository = static::getContainer()->get(MediaRepository::class);
        $this->mockS3Provider = static::getContainer()->get(BucketProviderInterface::class);

        self::assertInstanceOf(
            MockS3ProviderService::class,
            $this->mockS3Provider,
            'Test environment should use MockS3ProviderService to prevent real S3 uploads'
        );
    }

    /**
     * @dataProvider invalidTokenProvider
     */
    public function testMultipartInitiateReturnsErrorWithInvalidTokens(?string $token, int $expectedStatusCode): void
    {
        $orderId = rand(10000, 99999);

        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $headers['HTTP_TOKEN'] = $token;
        }

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/initiate/order_id/{$orderId}",
            [],
            [],
            $headers,
            json_encode(['filename' => 'test.jpg', 'mimeType' => 'image/jpeg'])
        );

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function testMultipartInitiateReturns400WithoutRequiredFields(): void
    {
        $orderId = rand(10000, 99999);
        $this->createProfileForOrder($orderId, 'test@example.com');

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/initiate/order_id/{$orderId}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode(['filename' => 'test.jpg'])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('filename and mimeType are required', $response['error']);
    }

    public function testMultipartInitiateSuccessfullyCreatesUpload(): void
    {
        $orderId = rand(10000, 99999);
        $this->createProfileForOrder($orderId, 'test@example.com');

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/initiate/order_id/{$orderId}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode(['filename' => 'video.mp4', 'mimeType' => 'video/mp4'])
        );

        self::assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('uploadId', $response);
        self::assertArrayHasKey('key', $response);
        self::assertNotEmpty($response['uploadId']);
        self::assertStringContainsString((string) $orderId, $response['key']);
        self::assertStringContainsString('video.mp4', $response['key']);
    }

    public function testMultipartPartReturns401WithoutToken(): void
    {
        $orderId = rand(10000, 99999);

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/part/orderId/{$orderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['key' => 'test-key', 'uploadId' => 'test-upload-id', 'partNumber' => 1])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testMultipartPartReturns400WithoutHashHeader(): void
    {
        $orderId = rand(10000, 99999);

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/part/orderId/{$orderId}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode(['key' => 'test-key', 'uploadId' => 'test-upload-id', 'partNumber' => 1])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('Missing hash header', $response['error']);
    }

    public function testMultipartPartReturns400WithoutRequiredFields(): void
    {
        $orderId = rand(10000, 99999);

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/part/orderId/{$orderId}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
                'HTTP_HASH' => 'test-hash'
            ],
            json_encode(['key' => 'test-key', 'uploadId' => 'test-upload-id'])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('key, uploadId and partNumber are required', $response['error']);
    }

    public function testMultipartPartReturnsPresignedUrl(): void
    {
        $orderId = rand(10000, 99999);

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/part/orderId/{$orderId}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
                'HTTP_HASH' => 'test-hash'
            ],
            json_encode(['key' => 'test-key', 'uploadId' => 'test-upload-id', 'partNumber' => 1])
        );

        self::assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('presignedUrl', $response);
        self::assertStringContainsString('mock-s3.example.com', $response['presignedUrl']);
        self::assertStringContainsString('partNumber=1', $response['presignedUrl']);
    }

    public function testMultipartCompleteReturns401WithoutToken(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/complete/eventUuid/{$uuid}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'key' => 'test-key',
                'uploadId' => 'test-upload-id',
                'filename' => 'test.mp4',
                'mimeType' => 'video/mp4',
                'fileSize' => 1024000,
                'parts' => [['PartNumber' => 1, 'ETag' => 'etag1']]
            ])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testMultipartCompleteReturns400WithoutHashHeader(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/complete/eventUuid/{$uuid}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode([
                'key' => 'test-key',
                'uploadId' => 'test-upload-id',
                'filename' => 'test.mp4',
                'mimeType' => 'video/mp4',
                'fileSize' => 1024000,
                'parts' => [['PartNumber' => 1, 'ETag' => 'etag1']]
            ])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('Missing hash header', $response['error']);
    }

    public function testMultipartCompleteReturns400WithoutRequiredFields(): void
    {
        $orderId = rand(10000, 99999);
        $profile = $this->createProfileForOrder($orderId, 'test@example.com');

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/complete/eventUuid/{$profile->getExternalId()}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
                'HTTP_HASH' => 'test-hash'
            ],
            json_encode([
                'key' => 'test-key',
                'uploadId' => 'test-upload-id',
                'filename' => 'test.mp4'
            ])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('key, uploadId, filename, mimeType, fileSize and parts are required', $response['error']);
    }

    public function testMultipartCompleteSuccessfullyCompletesUpload(): void
    {
        $orderId = rand(10000, 99999);
        $profile = $this->createProfileForOrder($orderId, 'test@example.com');
        $hash = 'client';

        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/complete/eventUuid/{$profile->getExternalId()}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
                'HTTP_HASH' => $hash
            ],
            json_encode([
                'key' => "{$orderId}/client/video.mp4",
                'uploadId' => 'mock-upload-123',
                'filename' => 'video.mp4',
                'mimeType' => 'video/mp4',
                'fileSize' => 5242880,
                'parts' => [
                    ['PartNumber' => 1, 'ETag' => 'etag-part-1'],
                    ['PartNumber' => 2, 'ETag' => 'etag-part-2']
                ]
            ])
        );

        self::assertResponseStatusCodeSame(201);

        // Verify media was saved to database
        $media = $this->mediaRepository->findOneBy(['event' => $profile]);
        self::assertNotNull($media, 'Media should be persisted to database');
        self::assertEquals("{$orderId}/client/video.mp4", $media->getFilePath());
        self::assertEquals('video/mp4', $media->getFileType());
        self::assertEquals(5242880, $media->getFileSize());
        self::assertEquals('video.mp4', $media->getOriginalFilename());

        // Verify no real S3 upload occurred (MockS3ProviderService is used in test environment)
        // The fact that we got a 201 response and the media is in the database confirms
        // that the upload workflow completed successfully without touching real S3
    }

    public function testMultipartAbortReturns401WithoutToken(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->client->request(
            'DELETE',
            "/api/v2/media/add/multipart/abort/eventUuid/{$uuid}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['key' => 'test-key', 'uploadId' => 'test-upload-id'])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testMultipartAbortReturns400WithoutHashHeader(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->client->request(
            'DELETE',
            "/api/v2/media/add/multipart/abort/eventUuid/{$uuid}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode(['key' => 'test-key', 'uploadId' => 'test-upload-id'])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('Missing hash header', $response['error']);
    }

    public function testMultipartAbortReturns400WithoutRequiredFields(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->client->request(
            'DELETE',
            "/api/v2/media/add/multipart/abort/eventUuid/{$uuid}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
                'HTTP_HASH' => 'test-hash'
            ],
            json_encode(['key' => 'test-key'])
        );

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('key and uploadId are required', $response['error']);
    }

    public function testMultipartAbortSuccessfullyAbortsUpload(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->mockS3Provider->reset();
        $uploadId = $this->mockS3Provider->createMultipartUpload('test/video.mp4', 'video/mp4');

        self::assertCount(1, $this->mockS3Provider->getActiveMultipartUploads());

        $this->client->request(
            'DELETE',
            "/api/v2/media/add/multipart/abort/eventUuid/{$uuid}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
                'HTTP_HASH' => 'test-hash'
            ],
            json_encode(['key' => 'test/video.mp4', 'uploadId' => $uploadId])
        );

        self::assertResponseStatusCodeSame(200);

        // Verify upload was aborted in mock S3
        self::assertCount(0, $this->mockS3Provider->getActiveMultipartUploads());
    }

    public function testFullMultipartUploadWorkflow(): void
    {
        $orderId = rand(10000, 99999);
        $profile = $this->createProfileForOrder($orderId, 'test@example.com');
        $hash = 'client';

        // Step 1: Initiate multipart upload
        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/initiate/order_id/{$orderId}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com')
            ],
            json_encode(['filename' => 'large-video.mp4', 'mimeType' => 'video/mp4'])
        );

        self::assertResponseStatusCodeSame(200);
        $initiateResponse = json_decode($this->client->getResponse()->getContent(), true);
        $uploadId = $initiateResponse['uploadId'];
        $key = $initiateResponse['key'];

        // Step 2: Get presigned URL for part 1
        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/part/orderId/{$orderId}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
                'HTTP_HASH' => $hash
            ],
            json_encode(['key' => $key, 'uploadId' => $uploadId, 'partNumber' => 1])
        );

        self::assertResponseStatusCodeSame(200);
        $partResponse = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('presignedUrl', $partResponse);

        // Step 3: Complete multipart upload
        $this->client->request(
            'POST',
            "/api/v2/media/add/multipart/complete/eventUuid/{$profile->getExternalId()}",
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('test@example.com'),
                'HTTP_HASH' => $hash
            ],
            json_encode([
                'key' => $key,
                'uploadId' => $uploadId,
                'filename' => 'large-video.mp4',
                'mimeType' => 'video/mp4',
                'fileSize' => 10485760,
                'parts' => [['PartNumber' => 1, 'ETag' => 'etag-1']]
            ])
        );

        self::assertResponseStatusCodeSame(201);

        // Verify complete workflow succeeded
        $media = $this->mediaRepository->findOneBy(['original_filename' => 'large-video.mp4']);
        self::assertNotNull($media);
        self::assertEquals(10485760, $media->getFileSize());

        // Verify no real S3 upload occurred (MockS3ProviderService is used in test environment)
        // The successful completion of all steps confirms the workflow works correctly
    }

    public static function invalidTokenProvider(): array
    {
        return [
            'no token' => [null, 401],
            'invalid token' => ['invalid-token', 400]
        ];
    }

    private function createProfileForOrder(int $orderId, string $email): object
    {
        // Create profile via API
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
                'client_email' => $email,
                'order_id' => $orderId
            ])
        );

        self::assertResponseStatusCodeSame(201);

        $profile = $this->profileRepository->findOneBy(['order_id' => $orderId]);
        self::assertNotNull($profile);

        // Set a name for the profile (required for EventFetchService)
        $profile->setName('Test Profile ' . $orderId);
        $this->profileRepository->save($profile);

        return $profile;
    }
}
