<?php

declare(strict_types = 1);

namespace App\Tests\functional\Service;

use App\Service\Bucket\BucketProviderInterface;
use App\Service\Bucket\MockS3ProviderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MockS3ProviderTest extends KernelTestCase
{
    public function testBucketProviderIsUsingMockInTestEnvironment(): void
    {
        self::bootKernel(['environment' => 'test']);
        $container = static::getContainer();

        $bucketProvider = $container->get(BucketProviderInterface::class);

        self::assertInstanceOf(
            MockS3ProviderService::class,
            $bucketProvider,
            'BucketProviderInterface should be using MockS3ProviderService in test environment'
        );
    }

    public function testMockS3DoesNotActuallyUploadToS3(): void
    {
        self::bootKernel(['environment' => 'test']);
        $container = static::getContainer();

        /** @var MockS3ProviderService $bucketProvider */
        $bucketProvider = $container->get(BucketProviderInterface::class);

        $bucketProvider->putObject('test/key.txt', 'test content', 'text/plain');

        $uploadedObjects = $bucketProvider->getUploadedObjects();

        self::assertArrayHasKey('test/key.txt', $uploadedObjects);
        self::assertEquals('test content', $uploadedObjects['test/key.txt']['body']);
        self::assertEquals('text/plain', $uploadedObjects['test/key.txt']['contentType']);
    }
}
