<?php

namespace App\Tests\unit\Service\Media;

use App\Exception\Media\UnauthorizedException;
use App\Repository\EventRepository;
use App\Repository\MediaRepository;
use App\Service\Bucket\BucketProviderInterface;
use App\Service\Media\MediaService;
use App\ValueObject\HashValueObject;
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
     * @dataProvider successfulDeleteDataProvider
     */
    public function testDeleteContentSucceedsWhenHashMatchesFolder(
        string $url,
        string $hash,
        string $expectedKey
    ): void {
        $this->mediaRepository
            ->expects($this->once())
            ->method('softDeleteByPath')
            ->with($expectedKey);

        $this->mediaService->deleteContent($url, new HashValueObject($hash));
    }

    /**
     * @dataProvider unauthorizedDeleteDataProvider
     */
    public function testDeleteContentThrowsUnauthorizedExceptionWhenHashDoesNotMatch(
        string $url,
        string $hash
    ): void {
        $this->mediaRepository
            ->expects($this->never())
            ->method('softDeleteByPath');

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Not authorized to delete this media');

        $this->mediaService->deleteContent($url, new HashValueObject($hash));
    }

    /**
     * @dataProvider invalidUrlDataProvider
     */
    public function testDeleteContentThrowsInvalidArgumentExceptionForInvalidUrl(string $url): void
    {
        $this->mediaRepository
            ->expects($this->never())
            ->method('softDeleteByPath');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL');

        $this->mediaService->deleteContent($url, new HashValueObject('somehash'));
    }

    public function testDeleteContentThrowsInvalidArgumentExceptionForInvalidKeyFormat(): void
    {
        $this->mediaRepository
            ->expects($this->never())
            ->method('softDeleteByPath');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key format');

        $this->mediaService->deleteContent(
            'https://bucket.s3.eu-west-1.amazonaws.com/onlyone',
            new HashValueObject('somehash')
        );
    }

    public static function successfulDeleteDataProvider(): array
    {
        return [
            'client folder delete' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/123/client/photo.jpg',
                'hash' => 'client',
                'expectedKey' => '123/client/photo.jpg',
            ],
            'guest hash folder delete' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/456/abc123hash/image.png',
                'hash' => 'abc123hash',
                'expectedKey' => '456/abc123hash/image.png',
            ],
            'url with encoded characters' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/789/myfolder/photo%20with%20spaces.jpg',
                'hash' => 'myfolder',
                'expectedKey' => '789/myfolder/photo with spaces.jpg',
            ],
        ];
    }

    public static function unauthorizedDeleteDataProvider(): array
    {
        return [
            'different guest hash' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/123/hash1/photo.jpg',
                'hash' => 'hash2',
            ],
        ];
    }

    public static function invalidUrlDataProvider(): array
    {
        return [
            'empty string' => ['url' => ''],
            'no path' => ['url' => 'https://bucket.s3.eu-west-1.amazonaws.com'],
            'invalid url format' => ['url' => 'not-a-valid-url'],
        ];
    }
}