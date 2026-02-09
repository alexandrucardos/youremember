<?php

namespace App\Tests\unit\Service\Media;

use App\Repository\EventRepository;
use App\Repository\MediaRepository;
use App\Service\Bucket\BucketProviderInterface;
use App\Service\Media\MediaService;
use PHPUnit\Framework\TestCase;

class MediaServiceTest extends TestCase
{
    private MediaService $mediaService;
    private MediaRepository $mediaRepository;
    private EventRepository $eventRepository;
    private BucketProviderInterface $bucketProvider;

    protected function setUp(): void
    {
        $this->bucketProvider = $this->createMock(BucketProviderInterface::class);
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->mediaRepository = $this->createMock(MediaRepository::class);

        $this->mediaService = new MediaService(
            $this->bucketProvider,
            $this->eventRepository,
            $this->mediaRepository,
            'test-bucket',
            'eu-west-1'
        );
    }

    /**
     * @dataProvider buildUrlDataProvider
     */
    public function testBuildUrlReturnsCorrectS3Url(string $prefix, string $expectedUrl): void
    {
        $result = $this->mediaService->buildUrl($prefix);

        $this->assertSame($expectedUrl, $result);
    }

    public static function buildUrlDataProvider(): array
    {
        return [
            'simple prefix' => [
                'prefix' => '123/client/photo.jpg',
                'expectedUrl' => 'https://test-bucket.s3.eu-west-1.amazonaws.com/123/client/photo.jpg',
            ],
            'prefix with spaces' => [
                'prefix' => '123/client/photo with spaces.jpg',
                'expectedUrl' => 'https://test-bucket.s3.eu-west-1.amazonaws.com/123/client/photo with spaces.jpg',
            ],
        ];
    }
}