<?php

namespace App\Tests\Service\Event;

use App\Service\Event\EventDataService;
use App\Service\MediatorS3Service;
use App\ValueObject\Event\EventFetchValueObject;
use PHPUnit\Framework\TestCase;

class EventDataServiceTest extends TestCase
{
    private EventDataService $eventDataService;
    private MediatorS3Service $mediatorS3Service;

    public function testFetchWithExistingBackgroundImage(): void
    {
        $eventUuid = '12345';
        $backgroundImage = 'https://example.com/background.jpg';
        $picturesUrls = [
            'https://example-bucket.s3.eu-west-1.amazonaws.com/12345/pictures/photo1.jpg',
            'https://example-bucket.s3.eu-west-1.amazonaws.com/12345/pictures/photo2.jpg'
        ];

        $eventFetchDto = new EventFetchValueObject(
            uuid: $eventUuid,
            name: 'Test Event',
            orderId: 123,
            backgroundImage: $backgroundImage
        );

        $this->mediatorS3Service
            ->expects($this->once())
            ->method('fetchContentUrls')
            ->with('12345/pictures')
            ->willReturn($picturesUrls);

        $this->mediatorS3Service
            ->expects($this->never())
            ->method('buildUrl');

        $result = $this->eventDataService->fetch($eventFetchDto);

        $this->assertEquals($backgroundImage, $result->backgroundPictureUrl);
        $this->assertEquals($picturesUrls, $result->pictures);
    }

    public function testFetchWithNullBackgroundImage(): void
    {
        $eventUuid = '12345';
        $backgroundImageUrl = 'https://example-bucket.s3.eu-west-1.amazonaws.com/12345/profile/background';
        $picturesUrls = [
            'https://example-bucket.s3.eu-west-1.amazonaws.com/12345/pictures/photo1.jpg',
            'https://example-bucket.s3.eu-west-1.amazonaws.com/12345/pictures/photo2.jpg'
        ];

        $eventFetchDto = new EventFetchValueObject(
            uuid: $eventUuid,
            name: 'Test Event',
            orderId: 123,
            backgroundImage: null
        );

        $this->mediatorS3Service
            ->expects($this->once())
            ->method('buildUrl')
            ->with('12345/profile/background')
            ->willReturn($backgroundImageUrl);

        $this->mediatorS3Service
            ->expects($this->once())
            ->method('fetchContentUrls')
            ->with('12345/pictures')
            ->willReturn($picturesUrls);

        $result = $this->eventDataService->fetch($eventFetchDto);

        $this->assertEquals($backgroundImageUrl, $result->backgroundPictureUrl);
        $this->assertEquals($picturesUrls, $result->pictures);
    }

    public function testFetchWithEmptyPicturesArray(): void
    {
        $eventUuid = '12345';
        $backgroundImage = 'https://example.com/background.jpg';
        $picturesUrls = [];

        $eventFetchDto = new EventFetchValueObject(
            uuid: $eventUuid,
            name: 'Test Event',
            orderId: 123,
            backgroundImage: $backgroundImage
        );

        $this->mediatorS3Service
            ->expects($this->once())
            ->method('fetchContentUrls')
            ->with('12345/pictures')
            ->willReturn($picturesUrls);

        $result = $this->eventDataService->fetch($eventFetchDto);

        $this->assertEquals($backgroundImage, $result->backgroundPictureUrl);
        $this->assertEmpty($result->pictures);
    }

    protected function setUp(): void
    {
        $this->mediatorS3Service = $this->createMock(MediatorS3Service::class);
        $this->eventDataService = new EventDataService($this->mediatorS3Service);
    }
}