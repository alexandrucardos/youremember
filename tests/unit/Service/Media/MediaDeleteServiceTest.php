<?php

declare(strict_types = 1);

namespace App\Tests\unit\Service\Media;

use App\Exception\Media\UnauthorizedException;
use App\Repository\MediaRepository;
use App\Service\Media\MediaDeleteService;
use App\ValueObject\HashValueObject;
use PHPUnit\Framework\TestCase;

class MediaDeleteServiceTest extends TestCase
{
    private MediaDeleteService $mediaDeleteService;
    private MediaRepository $mediaRepository;

    protected function setUp(): void
    {
        $this->mediaRepository = $this->createMock(MediaRepository::class);

        $this->mediaDeleteService = new MediaDeleteService($this->mediaRepository);
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

        $this->mediaDeleteService->deleteContent($url, new HashValueObject($hash));
    }

    /**
     * @dataProvider unauthorizedDeleteDataProvider
     */
    public function testDeleteContentThrowsUnauthorizedExceptionWhenHashDoesNotMatch(
        string $url,
        string $hash
    ): void {
        $this->mediaRepository->expects($this->never())->method('softDeleteByPath');

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Not authorized to delete this media');

        $this->mediaDeleteService->deleteContent($url, new HashValueObject($hash));
    }

    /**
     * @dataProvider invalidUrlDataProvider
     */
    public function testDeleteContentThrowsInvalidArgumentExceptionForInvalidUrl(string $url): void
    {
        $this->mediaRepository->expects($this->never())->method('softDeleteByPath');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL');

        $this->mediaDeleteService->deleteContent($url, new HashValueObject('somehash'));
    }

    public function testDeleteContentThrowsInvalidArgumentExceptionForInvalidKeyFormat(): void
    {
        $this->mediaRepository->expects($this->never())->method('softDeleteByPath');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key format');

        $this->mediaDeleteService->deleteContent(
            'https://bucket.s3.eu-west-1.amazonaws.com/onlyone',
            new HashValueObject('somehash')
        );
    }

    /**
     * @dataProvider deleteByUrlDataProvider
     */
    public function testDeleteByUrlSucceeds(string $url, string $expectedKey): void
    {
        $this->mediaRepository
            ->expects($this->once())
            ->method('softDeleteByPath')
            ->with($expectedKey);

        $this->mediaDeleteService->deleteByUrl($url);
    }

    public static function successfulDeleteDataProvider(): array
    {
        return [
            'client folder delete' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/123/client/photo.jpg',
                'hash' => 'client',
                'expectedKey' => '123/client/photo.jpg'
            ],
            'guest hash folder delete' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/456/abc123hash/image.png',
                'hash' => 'abc123hash',
                'expectedKey' => '456/abc123hash/image.png'
            ],
            'url with encoded characters' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/789/myfolder/photo%20with%20spaces.jpg',
                'hash' => 'myfolder',
                'expectedKey' => '789/myfolder/photo with spaces.jpg'
            ]
        ];
    }

    public static function unauthorizedDeleteDataProvider(): array
    {
        return [
            'different guest hash' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/123/hash1/photo.jpg',
                'hash' => 'hash2'
            ]
        ];
    }

    public static function invalidUrlDataProvider(): array
    {
        return [
            'empty string' => ['url' => ''],
            'no path' => ['url' => 'https://bucket.s3.eu-west-1.amazonaws.com'],
            'invalid url format' => ['url' => 'not-a-valid-url']
        ];
    }

    public static function deleteByUrlDataProvider(): array
    {
        return [
            'simple url' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/123/client/photo.jpg',
                'expectedKey' => '123/client/photo.jpg'
            ],
            'url with encoded characters' => [
                'url' => 'https://bucket.s3.eu-west-1.amazonaws.com/789/myfolder/photo%20with%20spaces.jpg',
                'expectedKey' => '789/myfolder/photo with spaces.jpg'
            ]
        ];
    }
}
