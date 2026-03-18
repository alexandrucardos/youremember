<?php

declare(strict_types = 1);

namespace App\Tests\unit\Service\Event;

use App\Entity\Event;
use App\Exception\Event\NotFoundException;
use App\Repository\EventRepository;
use App\Service\Event\EventUpdateService;
use App\ValueObject\ProfileNameFontValueObject;
use App\ValueObject\ProfileNameValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

class EventUpdateServiceTest extends TestCase
{
    private EventUpdateService $eventUpdateService;
    private EventRepository $eventRepository;

    public static function successfulUpdateNameDataProvider(): array
    {
        return [
            'simple name update' => [
                'orderId' => 123,
                'newName' => 'Birthday Party',
                'font' => 'font1'
            ],
            'name with special characters' => [
                'orderId' => 456,
                'newName' => 'Wedding - John & Jane',
                'font' => 'font2'
            ],
            'unicode name' => [
                'orderId' => 789,
                'newName' => 'Petrecere de Crăciun'
            ]
        ];
    }

    /**
     * @dataProvider successfulUpdateNameDataProvider
     */
    public function testUpdateNameSucceedsWhenEventExists(
        int $orderId,
        string $newName,
        string $font = null
    ): void {
        $event = new Event();
        $event->setName('Old Name');

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['order_id' => $orderId])
            ->willReturn($event);

        $this->eventRepository
            ->expects($this->once())
            ->method('save')
            ->with($event);

        $result = $this->eventUpdateService->updateName(
            new OrderIdValueObject($orderId),
            new ProfileNameValueObject($newName),
            new ProfileNameFontValueObject($font)
        );

        $this->assertSame($event, $result);
        $this->assertSame($newName, $result->getName());
    }

    public function testUpdateNameThrowsNotFoundExceptionWhenEventDoesNotExist(): void
    {
        $orderId = 999;

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['order_id' => $orderId])
            ->willReturn(null);

        $this->eventRepository->expects($this->never())->method('save');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Event not found');

        $this->eventUpdateService->updateName(
            new OrderIdValueObject($orderId),
            new ProfileNameValueObject('New Name'),
            new ProfileNameFontValueObject('Oswald')
        );
    }

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepository::class);

        $this->eventUpdateService = new EventUpdateService($this->eventRepository);
    }
}
