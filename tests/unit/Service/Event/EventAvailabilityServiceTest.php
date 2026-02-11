<?php

namespace App\Tests\unit\Service\Event;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\Event\EventAvailabilityService;
use App\ValueObject\Status;
use PHPUnit\Framework\TestCase;

class EventAvailabilityServiceTest extends TestCase
{
    private EventAvailabilityService $eventAvailabilityService;
    private EventRepository $eventRepository;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepository::class);

        $this->eventAvailabilityService = new EventAvailabilityService(
            $this->eventRepository,
        );
    }

    public function testInvokeReturnsTrueWhenEventFoundByOrderId(): void
    {
        $orderId = '123';
        $event = new Event();

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'order_id' => $orderId,
                'status' => Status::VALID
            ])
            ->willReturn($event);

        $result = ($this->eventAvailabilityService)($orderId, null);

        $this->assertTrue($result);
    }

    public function testInvokeReturnsFalseWhenEventNotFoundByOrderId(): void
    {
        $orderId = '123';

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'order_id' => $orderId,
                'status' => Status::VALID
            ])
            ->willReturn(null);

        $result = ($this->eventAvailabilityService)($orderId, null);

        $this->assertFalse($result);
    }

    public function testInvokeReturnsTrueWhenEventFoundByUuid(): void
    {
        $uuid = 'abc-123-def';
        $event = new Event();

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'uuid' => $uuid,
                'status' => Status::VALID
            ])
            ->willReturn($event);

        $result = ($this->eventAvailabilityService)(null, $uuid);

        $this->assertTrue($result);
    }

    public function testInvokeReturnsFalseWhenEventNotFoundByUuid(): void
    {
        $uuid = 'abc-123-def';

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'uuid' => $uuid,
                'status' => Status::VALID
            ])
            ->willReturn(null);

        $result = ($this->eventAvailabilityService)(null, $uuid);

        $this->assertFalse($result);
    }

    public function testInvokePrioritizesOrderIdOverUuid(): void
    {
        $orderId = '123';
        $uuid = 'abc-123-def';
        $event = new Event();

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'order_id' => $orderId,
                'status' => Status::VALID
            ])
            ->willReturn($event);

        $result = ($this->eventAvailabilityService)($orderId, $uuid);

        $this->assertTrue($result);
    }

    public function testInvokeThrowsRuntimeExceptionWhenBothParametersAreNull(): void
    {
        $this->eventRepository
            ->expects($this->never())
            ->method('findOneBy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid data provided!');

        ($this->eventAvailabilityService)(null, null);
    }
}