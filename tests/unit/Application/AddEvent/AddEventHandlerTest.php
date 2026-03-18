<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\AddEvent;

use App\Application\AddEvent\AddEventCommand;
use App\Application\AddEvent\AddEventHandler;
use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Service\UuidInterface;
use App\Domain\ValueObject\EventStartDateValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\OrderStatusValueObject;
use PHPUnit\Framework\TestCase;

class AddEventHandlerTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    public static function validEventDataProvider(): array
    {
        return [
            'standard order' => [
                'order_id' => 1,
                'email' => 'aa@b.com',
                'event_start_date' => new \DateTimeImmutable('now +20 days'),
                'needsManualProcessing' => true
            ]
        ];
    }

    /**
     * @dataProvider validEventDataProvider
     */
    public function testInvokeSavesEvent(
        int $orderId,
        string $email,
        \DateTimeImmutable $eventStartDate,
        $needsManualProcessing
    ): void {
        $uuidService = $this->createMock(UuidInterface::class);
        $uuidService->method('generate')->willReturn(self::UUID);

        $eventRepository = $this->createMock(EventRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('saveEvent')
            ->with($this->callback(
                static fn(EventEntity $eventEntity) => (
                    $eventEntity->getOrderId()->value === $orderId
                    && $eventEntity->eventUuidValueObject->value === self::UUID
                    && $eventEntity->needsManualProcessing() === $needsManualProcessing
                    && $eventEntity->getEventStartDate()->value->format('Y-m-d H:i:s') === $eventStartDate->format(
                        'Y-m-d H:i:s'
                    )
                )
            ));

        $command = new AddEventCommand(
            orderIdValueObject: new OrderIdValueObject($orderId),
            emailValueObject: new EmailValueObject($email),
            eventStartDateValueObject: new EventStartDateValueObject($eventStartDate->format('Y-m-d H:i:s')),
            orderStatusValueObject: new OrderStatusValueObject('completed'),
            needsManualProcessing: $needsManualProcessing
        );

        $handler = new AddEventHandler($eventRepository, $uuidService);
        $handler($command);
    }
}
