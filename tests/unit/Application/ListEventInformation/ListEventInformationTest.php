<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\ListEventInformation;

use App\Application\ListEventInformation\ListEventInformation;
use App\Application\ListEventInformation\ListEventInformationQuery;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\Exception\ListEventInformationQueryNullException;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;

class ListEventInformationTest extends TestCase
{
    public static function uuidQueryDataProvider(): array
    {
        return [
            'first uuid' => [
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'orderId' => 1
            ]
        ];
    }

    public static function orderIdQueryDataProvider(): array
    {
        return [
            'first order' => [
                'orderId' => 1
            ]
        ];
    }

    public function testInvokeThrowsWhenBothUuidAndOrderIdAreNull(): void
    {
        $eventRepository = $this->createMock(EventRepositoryInterface::class);
        $eventRepository->expects($this->never())->method('fetchOrderIdForUuid');
        $eventRepository->expects($this->never())->method('fetchEventViewModelForEvent');

        $this->expectException(ListEventInformationQueryNullException::class);

        $handler = new ListEventInformation($eventRepository);
        $handler(new ListEventInformationQuery());
    }

    /**
     * @dataProvider uuidQueryDataProvider
     */
    public function testInvokeWithUuidFetchesEventViewModelViaOrderIdLookup(string $uuid, int $orderId): void
    {
        $eventRepository = $this->createMock(EventRepositoryInterface::class);
        $eventRepository->expects($this->once())->method('fetchOrderIdForUuid')->with($uuid)->willReturn($orderId);

        $eventRepository
            ->expects($this->once())
            ->method('fetchEventViewModelForEvent')
            ->with($this->callback(
                static fn(OrderIdValueObject $orderIdValueObject): bool => $orderIdValueObject->value === $orderId
            ));

        $query = new ListEventInformationQuery(uuid: new UuidValueObject($uuid));

        $handler = new ListEventInformation($eventRepository);
        $handler($query);
    }

    /**
     * @dataProvider orderIdQueryDataProvider
     */
    public function testInvokeWithOrderIdFetchesEventViewModelDirectly(int $orderId): void
    {
        $eventRepository = $this->createMock(EventRepositoryInterface::class);
        $eventRepository->expects($this->never())->method('fetchOrderIdForUuid');

        $eventRepository
            ->expects($this->once())
            ->method('fetchEventViewModelForEvent')
            ->with($this->callback(
                static fn(OrderIdValueObject $orderIdValueObject): bool => $orderIdValueObject->value === $orderId
            ));

        $query = new ListEventInformationQuery(orderId: new OrderIdValueObject($orderId));

        $handler = new ListEventInformation($eventRepository);
        $handler($query);
    }
}
