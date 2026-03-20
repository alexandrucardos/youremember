<?php

declare(strict_types = 1);

namespace App\Tests\unit\Service\Media;

use App\Repository\MediaRepository;
use App\Service\Media\MediaDeleteService;
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
