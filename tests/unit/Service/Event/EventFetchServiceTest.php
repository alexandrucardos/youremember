<?php

namespace App\Tests\unit\Service\Event;

use App\Entity\Event;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;
use App\Service\Event\EventFetchService;
use App\Service\Media\MediaService;
use App\ValueObject\Event\EventFetchValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

final class EventFetchServiceTest extends TestCase
{
    public function testFetchByOrderIdThrowsNotFoundExceptionWhenEventNotFound(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $s3Service = $this->createMock(MediaService::class);

        $orderId = 123;

        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['order_id' => $orderId])
            ->willReturn(null);

        $service = new EventFetchService($repo, $s3Service);

        $this->expectException(NotFoundException::class);
        $service->fetchByOrderId(new OrderIdValueObject($orderId));
    }

    public function testFetchByOrderIdReturnsEventFetchDtoWhenFound(): void
    {
        $orderId = 123;

        $event = (new Event())
            ->setUuid('550e8400-e29b-41d4-a716-446655440000')
            ->setName('Test Event')
            ->setOrderId($orderId);

        $expectedBackgroundUrl = 'https://s3.example.com/123/client/background.webp';

        $repo = $this->createMock(EventRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['order_id' => $orderId])
            ->willReturn($event);

        $s3Service = $this->createMock(MediaService::class);
        $s3Service->expects(self::once())
            ->method('buildUrl')
            ->with(sprintf('%d/%s/%s', $orderId, MediaService::FOLDER_CLIENT, MediaService::FILE_BACKGROUND_NAME))
            ->willReturn($expectedBackgroundUrl);

        $service = new EventFetchService($repo, $s3Service);

        $result = $service->fetchByOrderId(new OrderIdValueObject($orderId));

        self::assertInstanceOf(EventFetchValueObject::class, $result);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $result->uuid);
        self::assertSame('Test Event', $result->name);
        self::assertSame($orderId, $result->orderId);
        self::assertSame($expectedBackgroundUrl, $result->backgroundImage);
    }
}