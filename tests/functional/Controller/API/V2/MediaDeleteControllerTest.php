<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API\V2;

use App\Domain\ValueObject\Status;
use App\Domain\ValueObject\UserRole;
use App\Entity\Media;
use App\Entity\Profile;
use App\Entity\User;
use App\Service\Bucket\BucketProviderInterface;
use App\Service\Bucket\MockS3ProviderService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MediaDeleteControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function testDeleteMediaReturns401WithoutToken(): void
    {
        $this->client->request(
            'DELETE',
            '/api/v2/media/delete/orderId/123',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testDeleteMediaReturns400WithInvalidToken(): void
    {
        $this->client->request(
            'DELETE',
            '/api/v2/media/delete/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'x'
            ],
            json_encode(['urls' => []])
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testDeleteMediaReturns401WithExpiredToken(): void
    {
        $this->client->request(
            'DELETE',
            '/api/v2/media/delete/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateExpiredToken('test@example.com')
            ],
            json_encode(['urls' => []])
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testDeleteMediaReturns403WithNonAdminRole(): void
    {
        $this->client->request(
            'DELETE',
            '/api/v2/media/delete/orderId/123',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => 'guest_token'
            ],
            json_encode(['urls' => []])
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteMediaSuccessfullyWithAdminToken(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $userRepository = $container->get('App\Repository\UserRepository');
        $profileRepository = $container->get('App\Repository\ProfileRepository');
        $mediaRepository = $container->get('App\Repository\MediaRepository');

        // Create or get a user
        $user = $userRepository->findOneBy(['email' => 'admin@eventsphotoshare.ro']);
        if (!$user) {
            $user = new User();
            $user->setEmail('admin@eventsphotoshare.ro')->setRole(UserRole::ROLE_ADMIN);
            $entityManager->persist($user);
        }

        // Create a profile
        $orderId = rand(10000, 99999);
        $profile = new Profile();
        $profile
            ->setExternalId($orderId)
            ->setOrderId($orderId)
            ->setName('Test Profile')
            ->setNameFont('classic')
            ->setStatus(Status::VALID)
            ->setUser($user);
        $entityManager->persist($profile);

        // Create media records
        $filePath1 = $orderId . '/client/test1.jpg';
        $thumbnailPath1 = $orderId . '/client/thumb1.jpg';
        $media1 = new Media();
        $media1
            ->setEvent($profile)
            ->setFilePath($filePath1)
            ->setThumbnailPath($thumbnailPath1)
            ->setFileType('image/jpeg')
            ->setFileSize(1024)
            ->setOriginalFilename('test1.jpg');
        $entityManager->persist($media1);

        $filePath2 = $orderId . '/client/test2.jpg';
        $thumbnailPath2 = $orderId . '/client/thumb2.jpg';
        $media2 = new Media();
        $media2
            ->setEvent($profile)
            ->setFilePath($filePath2)
            ->setThumbnailPath($thumbnailPath2)
            ->setFileType('image/jpeg')
            ->setFileSize(2048)
            ->setOriginalFilename('test2.jpg');
        $entityManager->persist($media2);

        $entityManager->flush();

        // Verify MockS3ProviderService is being used
        $bucketProvider = $container->get(BucketProviderInterface::class);
        self::assertInstanceOf(
            MockS3ProviderService::class,
            $bucketProvider,
            'BucketProviderInterface should be using MockS3ProviderService in test environment'
        );

        // Delete the first media (URLs are converted to keys by the service)
        $this->client->request(
            'DELETE',
            '/api/v2/media/delete/orderId/' . $orderId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin@eventsphotoshare.ro')
            ],
            json_encode([
                'urls' => [
                    'https://s3.amazonaws.com/' . $filePath1
                ]
            ])
        );

        self::assertResponseStatusCodeSame(200);

        // Verify the media was soft deleted
        $entityManager->clear();
        $deletedMedia = $mediaRepository->find($media1->getId());

        self::assertNotNull($deletedMedia, 'Media should still exist in database');
        self::assertNotNull($deletedMedia->getDeletedAt(), 'Media should have deleted_at timestamp');
        self::assertTrue($deletedMedia->isDeleted(), 'Media should be marked as deleted');

        // Verify the second media is still active
        $activeMedia = $mediaRepository->find($media2->getId());
        self::assertNull($activeMedia->getDeletedAt(), 'Second media should not be deleted');
        self::assertFalse($activeMedia->isDeleted(), 'Second media should not be marked as deleted');
    }

    public function testDeleteMultipleMediaSuccessfully(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $userRepository = $container->get('App\Repository\UserRepository');
        $mediaRepository = $container->get('App\Repository\MediaRepository');

        // Create or get a user
        $user = $userRepository->findOneBy(['email' => 'admin2@eventsphotoshare.ro']);
        if (!$user) {
            $user = new User();
            $user->setEmail('admin2@eventsphotoshare.ro')->setRole(UserRole::ROLE_ADMIN);
            $entityManager->persist($user);
        }

        // Create a profile
        $orderId = rand(10000, 99999);
        $profile = new Profile();
        $profile
            ->setExternalId($orderId)
            ->setOrderId($orderId)
            ->setName('Test Profile 2')
            ->setNameFont('classic')
            ->setStatus(Status::VALID)
            ->setUser($user);
        $entityManager->persist($profile);

        // Create multiple media records
        $mediaIds = [];
        $filePaths = [];
        for ($i = 1; $i <= 3; $i++) {
            $filePath = $orderId . '/client/test' . $i . '.jpg';
            $thumbnailPath = $orderId . '/client/thumb' . $i . '.jpg';
            $filePaths[] = $filePath;

            $media = new Media();
            $media
                ->setEvent($profile)
                ->setFilePath($filePath)
                ->setThumbnailPath($thumbnailPath)
                ->setFileType('image/jpeg')
                ->setFileSize(1024 * $i)
                ->setOriginalFilename('test' . $i . '.jpg');
            $entityManager->persist($media);
            $entityManager->flush();
            $mediaIds[] = $media->getId();
        }

        // Delete multiple media files
        $this->client->request(
            'DELETE',
            '/api/v2/media/delete/orderId/' . $orderId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin2@eventsphotoshare.ro')
            ],
            json_encode([
                'urls' => [
                    'https://s3.amazonaws.com/' . $filePaths[0],
                    'https://s3.amazonaws.com/' . $filePaths[1]
                ]
            ])
        );

        self::assertResponseStatusCodeSame(200);

        // Verify the first two media were soft deleted
        $entityManager->clear();
        $deletedMedia1 = $mediaRepository->find($mediaIds[0]);
        $deletedMedia2 = $mediaRepository->find($mediaIds[1]);
        $activeMedia3 = $mediaRepository->find($mediaIds[2]);

        self::assertNotNull($deletedMedia1->getDeletedAt(), 'First media should be deleted');
        self::assertNotNull($deletedMedia2->getDeletedAt(), 'Second media should be deleted');
        self::assertNull($activeMedia3->getDeletedAt(), 'Third media should remain active');
    }

    public function testDeleteMediaDoesNotUploadToRealS3(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $userRepository = $container->get('App\Repository\UserRepository');

        // Create or get a user
        $user = $userRepository->findOneBy(['email' => 'admin3@eventsphotoshare.ro']);
        if (!$user) {
            $user = new User();
            $user->setEmail('admin3@eventsphotoshare.ro')->setRole(UserRole::ROLE_ADMIN);
            $entityManager->persist($user);
        }

        // Create a profile
        $orderId = rand(10000, 99999);
        $profile = new Profile();
        $profile
            ->setExternalId($orderId)
            ->setOrderId($orderId)
            ->setName('Test Profile 3')
            ->setNameFont('classic')
            ->setStatus(Status::VALID)
            ->setUser($user);
        $entityManager->persist($profile);

        // Create media
        $filePath = $orderId . '/client/test.jpg';
        $thumbnailPath = $orderId . '/client/thumb.jpg';
        $media = new Media();
        $media
            ->setEvent($profile)
            ->setFilePath($filePath)
            ->setThumbnailPath($thumbnailPath)
            ->setFileType('image/jpeg')
            ->setFileSize(1024)
            ->setOriginalFilename('test.jpg');
        $entityManager->persist($media);
        $entityManager->flush();

        /** @var MockS3ProviderService $bucketProvider */
        $bucketProvider = $container->get(BucketProviderInterface::class);

        // Verify no objects uploaded before delete
        $uploadedObjects = $bucketProvider->getUploadedObjects();
        self::assertEmpty($uploadedObjects, 'No objects should be uploaded to S3 mock before delete');

        // Delete the media
        $this->client->request(
            'DELETE',
            '/api/v2/media/delete/orderId/' . $orderId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_TOKEN' => $this->generateValidToken('admin3@eventsphotoshare.ro')
            ],
            json_encode([
                'urls' => [
                    'https://s3.amazonaws.com/' . $filePath
                ]
            ])
        );

        self::assertResponseStatusCodeSame(200);

        // Verify still no objects uploaded after delete
        $uploadedObjectsAfter = $bucketProvider->getUploadedObjects();
        self::assertEmpty($uploadedObjectsAfter, 'Delete operation should not upload anything to S3 mock');
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
