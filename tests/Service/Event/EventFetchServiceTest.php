<?php

namespace App\Tests\Service\Event;

use App\DTO\Event\EventFetchDto;
use App\Entity\Event;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;
use App\Service\Event\EventFetchService;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

final class EventFetchServiceTest extends TestCase
{
    public function testFetchByOrderIdThrowsNotFoundExceptionWhenEventNotFound(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->expects(self::once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $service = new EventFetchService($repo);

        $this->expectException(NotFoundException::class);
        $service->fetchByOrderId(new OrderIdValueObject(123));
    }

    public function testFetchByOrderIdReturnsEventFetchDtoWhenFound(): void
    {
        $event = (new Event())
            ->setUuid('550e8400-e29b-41d4-a716-446655440000')
            ->setName('Test Event')
            ->setOrderId(123);

        $repo = $this->createMock(EventRepository::class);
        $repo->expects(self::once())
            ->method('find')
            ->with(123)
            ->willReturn($event);

        $service = new EventFetchService($repo);

        $result = $service->fetchByOrderId(new OrderIdValueObject(123));

        self::assertInstanceOf(EventFetchDto::class, $result);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $result->uuid);
        self::assertSame('Test Event', $result->name);
        self::assertSame(123, $result->orderId);
        self::assertNull($result->backgroundImage);
    }
}