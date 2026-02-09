<?php

namespace App\Tests\unit\Service\Event;

use App\Repository\MediaRepository;
use App\Service\Event\EventDataService;
use App\Service\Media\MediaService;
use App\ValueObject\Event\EventFetchValueObject;
use PHPUnit\Framework\TestCase;

class EventDataServiceTest extends TestCase
{
    private EventDataService $eventDataService;
    private MediaRepository $mediaRepository;
    private MediaService $mediatorS3Service;

    public function testFetchWithExistingBackgroundImage(): void
    {
        $eventUuid = '12345';
        $orderId = 123;
        $backgroundImage = 'https://example.com/background.jpg';
        $picturesUrls = [
            'https://example-bucket.s3.eu-west-1.amazonaws.com/12345/client/photo1.jpg',
            'https://example-bucket.s3.eu-west-1.amazonaws.com/12345/client/photo2.jpg'
        ];

        $eventFetchDto = new EventFetchValueObject(
            uuid: $eventUuid,
            name: 'Test Event',
            orderId: $orderId,
            backgroundImage: $backgroundImage
        );

        $this->mediaRepository
            ->expects($this->once())
            ->method('findPathsByOrderId')
            ->with($orderId)
            ->willReturn([
                ['123/client/photo1.jpg'],
                ['123/client/photo2.jpg'],
            ]);

        $this->mediatorS3Service
            ->expects($this->exactly(2))
            ->method('buildUrl')
            ->willReturnCallback(fn(string $path) => match ($path) {
                '123/client/photo1.jpg' => $picturesUrls[0],
                '123/client/photo2.jpg' => $picturesUrls[1],
            });

        $result = $this->eventDataService->fetch($eventFetchDto);

        $this->assertEquals($backgroundImage, $result->backgroundPictureUrl);
        $this->assertEquals($picturesUrls, $result->pictures);
    }

    public function testFetchWithNullBackgroundImage(): void
    {
        $eventUuid = '12345';
        $orderId = 123;
        $backgroundImageUrl = 'https://example-bucket.s3.eu-west-1.amazonaws.com/123/client/background.webp';
        $picturesUrls = [
            'https://example-bucket.s3.eu-west-1.amazonaws.com/123/client/photo1.jpg',
            'https://example-bucket.s3.eu-west-1.amazonaws.com/123/client/photo2.jpg'
        ];

        $eventFetchDto = new EventFetchValueObject(
            uuid: $eventUuid,
            name: 'Test Event',
            orderId: $orderId,
            backgroundImage: null
        );

        $this->mediaRepository
            ->expects($this->once())
            ->method('findPathsByOrderId')
            ->with($orderId)
            ->willReturn([
                ['123/client/photo1.jpg'],
                ['123/client/photo2.jpg'],
            ]);

        $this->mediatorS3Service
            ->expects($this->exactly(3))
            ->method('buildUrl')
            ->willReturnCallback(fn(string $path) => match ($path) {
                sprintf('%d/%s/%s', $orderId, MediaService::FOLDER_CLIENT, MediaService::FILE_BACKGROUND_NAME) => $backgroundImageUrl,
                '123/client/photo1.jpg' => $picturesUrls[0],
                '123/client/photo2.jpg' => $picturesUrls[1],
            });

        $result = $this->eventDataService->fetch($eventFetchDto);

        $this->assertEquals($backgroundImageUrl, $result->backgroundPictureUrl);
        $this->assertEquals($picturesUrls, $result->pictures);
    }

    public function testFetchWithEmptyPicturesArray(): void
    {
        $eventUuid = '12345';
        $orderId = 123;
        $backgroundImage = 'https://example.com/background.jpg';

        $eventFetchDto = new EventFetchValueObject(
            uuid: $eventUuid,
            name: 'Test Event',
            orderId: $orderId,
            backgroundImage: $backgroundImage
        );

        $this->mediaRepository
            ->expects($this->once())
            ->method('findPathsByOrderId')
            ->with($orderId)
            ->willReturn([]);

        $result = $this->eventDataService->fetch($eventFetchDto);

        $this->assertEquals($backgroundImage, $result->backgroundPictureUrl);
        $this->assertEmpty($result->pictures);
    }

    protected function setUp(): void
    {
        $this->mediatorS3Service = $this->createMock(MediaService::class);
        $this->mediaRepository = $this->createMock(MediaRepository::class);

        $this->eventDataService = new EventDataService(
            $this->mediatorS3Service,
            $this->mediaRepository,
        );
    }
}