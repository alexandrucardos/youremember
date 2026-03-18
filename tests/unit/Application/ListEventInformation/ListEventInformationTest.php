<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\ListEventInformation;

use App\Application\ListEventInformation\ListProfileInformation;
use App\Application\ListEventInformation\ListProfileInformationQuery;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ListProfileInformationQueryNullException;
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
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository->expects($this->never())->method('fetchOrderIdForUuid');
        $eventRepository->expects($this->never())->method('fetchEventViewModelForEvent');

        $this->expectException(ListProfileInformationQueryNullException::class);

        $handler = new ListProfileInformation($eventRepository);
        $handler(new ListProfileInformationQuery());
    }

    /**
     * @dataProvider uuidQueryDataProvider
     */
    public function testInvokeWithUuidFetchesEventViewModelViaOrderIdLookup(string $uuid, int $orderId): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository->expects($this->once())->method('fetchOrderIdForUuid')->with($uuid)->willReturn($orderId);

        $eventRepository
            ->expects($this->once())
            ->method('fetchEventViewModelForEvent')
            ->with($this->callback(
                static fn(OrderIdValueObject $orderIdValueObject): bool => $orderIdValueObject->value === $orderId
            ));

        $query = new ListProfileInformationQuery(uuid: new UuidValueObject($uuid));

        $handler = new ListProfileInformation($eventRepository);
        $handler($query);
    }

    /**
     * @dataProvider orderIdQueryDataProvider
     */
    public function testInvokeWithOrderIdFetchesEventViewModelDirectly(int $orderId): void
    {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository->expects($this->never())->method('fetchOrderIdForUuid');

        $eventRepository
            ->expects($this->once())
            ->method('fetchEventViewModelForEvent')
            ->with($this->callback(
                static fn(OrderIdValueObject $orderIdValueObject): bool => $orderIdValueObject->value === $orderId
            ));

        $query = new ListProfileInformationQuery(orderId: new OrderIdValueObject($orderId));

        $handler = new ListProfileInformation($eventRepository);
        $handler($query);
    }
}
