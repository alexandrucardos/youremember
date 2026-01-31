<?php

namespace App\Tests\Service\Event;

use App\DTO\Event\EventFetchDto;
use App\Entity\Event;
use App\Exception\Event\InvalidOrderIdException;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;
use App\Service\Event\EventFetchService;
use PHPUnit\Framework\TestCase;

final class EventFetchServiceTest extends TestCase
{
    public function testFetchByOrderIdThrowsInvalidOrderIdExceptionForNonNumericId(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->expects(self::never())->method('find');

        $service = new EventFetchService($repo);

        $this->expectException(InvalidOrderIdException::class);
        $service->fetchByOrderId('invalid');
    }

    public function testFetchByOrderIdThrowsInvalidOrderIdExceptionForZero(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->expects(self::never())->method('find');

        $service = new EventFetchService($repo);

        $this->expectException(InvalidOrderIdException::class);
        $service->fetchByOrderId(0);
    }

    public function testFetchByOrderIdThrowsInvalidOrderIdExceptionForNegativeId(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->expects(self::never())->method('find');

        $service = new EventFetchService($repo);

        $this->expectException(InvalidOrderIdException::class);
        $service->fetchByOrderId(-5);
    }

    public function testFetchByOrderIdThrowsNotFoundExceptionWhenEventNotFound(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->expects(self::once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $service = new EventFetchService($repo);

        $this->expectException(NotFoundException::class);
        $service->fetchByOrderId(123);
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

        $result = $service->fetchByOrderId(123);

        self::assertInstanceOf(EventFetchDto::class, $result);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $result->uuid);
        self::assertSame('Test Event', $result->name);
        self::assertSame(123, $result->orderId);
        self::assertNull($result->backgroundImage);
    }

    public function testFetchByOrderIdAcceptsStringNumericId(): void
    {
        $event = (new Event())
            ->setUuid('550e8400-e29b-41d4-a716-446655440000')
            ->setName('Test Event')
            ->setOrderId(456);

        $repo = $this->createMock(EventRepository::class);
        $repo->expects(self::once())
            ->method('find')
            ->with(456)
            ->willReturn($event);

        $service = new EventFetchService($repo);

        $result = $service->fetchByOrderId('456');

        self::assertInstanceOf(EventFetchDto::class, $result);
    }
}
