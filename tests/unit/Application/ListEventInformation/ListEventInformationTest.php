<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\ListEventInformation;

use App\Application\ListProfileInformation\ListProfileInformation;
use App\Application\ListProfileInformation\ListProfileInformationQuery;
use App\Application\ListProfileInformation\ProfileViewModel;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

class ListEventInformationTest extends TestCase
{
    public static function orderIdQueryDataProvider(): array
    {
        return [
            'first order' => ['orderId' => 1],
            'second order' => ['orderId' => 2]
        ];
    }

    /**
     * @dataProvider orderIdQueryDataProvider
     */
    public function testInvokeWithOrderIdFetchesEventViewModelDirectly(int $orderId): void
    {
        $mockViewModel = $this->createMock(ProfileViewModel::class);

        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('fetchProfileViewModelForOrderId')
            ->with($this->callback(
                static fn(OrderIdValueObject $orderIdValueObject): bool => $orderIdValueObject->value === $orderId
            ))
            ->willReturn($mockViewModel);

        $query = new ListProfileInformationQuery(orderIdValueObject: new OrderIdValueObject($orderId));

        $handler = new ListProfileInformation($eventRepository);
        $result = $handler($query);

        $this->assertSame($mockViewModel, $result);
    }
}
