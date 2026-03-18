<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\UpdateEventStatus;

use App\Application\UpdateEventStatus\UpdateEventBackgroundHandler;
use App\Application\UpdateEventStatus\UpdateEventStatusCommand;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use App\ValueObject\Status;
use PHPUnit\Framework\TestCase;

class UpdateEventStatusHandlerTest extends TestCase
{
    public static function validStatusDataProvider(): array
    {
        return [
            'processing order' => [
                'order_id' => 1,
                'order_status' => 'processing',
                'expected_status' => Status::INVALID
            ],
            'completed order' => [
                'order_id' => 2,
                'order_status' => 'completed',
                'expected_status' => Status::VALID
            ]
        ];
    }

    public static function notFoundOrderDataProvider(): array
    {
        return [
            'order with no event' => ['order_id' => 999]
        ];
    }

    /**
     * @dataProvider validStatusDataProvider
     */
    public function testInvokeUpdatesEventStatus(
        int $orderId,
        string $orderStatus,
        Status $expectedStatus
    ): void {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('getExistingEventUuidForOrderId')
            ->with($this->callback(
                static fn(OrderIdValueObject $orderIdValueObject): bool => $orderIdValueObject->value === $orderId
            ))
            ->willReturn($uuid);

        $eventRepository
            ->expects($this->once())
            ->method('updateEventStatus')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity): bool => (
                    $eventEntity->eventUuidValueObject->value === $uuid
                    && $eventEntity->getStatus() === $expectedStatus
                )
            ));

        $command = new UpdateEventStatusCommand(
            new OrderIdValueObject($orderId),
            new OrderStatusValueObject($orderStatus)
        );

        $handler = new UpdateEventBackgroundHandler($eventRepository);
        $handler($command);
    }

    /**
     * @dataProvider notFoundOrderDataProvider
     */
    public function testInvokeThrowsEventNotFoundExceptionWhenUuidIsNull(int $orderId): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository->expects($this->once())->method('getExistingEventUuidForOrderId')->willReturn(null);

        $eventRepository->expects($this->never())->method('updateEventStatus');

        $this->expectException(ProfileNotFoundException::class);

        $command = new UpdateEventStatusCommand(
            new OrderIdValueObject($orderId),
            new OrderStatusValueObject('processing')
        );

        $handler = new UpdateEventBackgroundHandler($eventRepository);
        $handler($command);
    }
}
