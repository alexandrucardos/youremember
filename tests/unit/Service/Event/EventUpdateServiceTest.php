<?php

declare(strict_types = 1);

namespace App\Tests\unit\Service\Event;

use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;
use App\Domain\ValueObject\ProfileNameValueObject;
use App\Entity\Profile;
use App\Exception\Event\NotFoundException;
use App\Repository\ProfileRepository;
use App\Service\Event\ProfileUpdateService;
use PHPUnit\Framework\TestCase;

class EventUpdateServiceTest extends TestCase
{
    private ProfileUpdateService $eventUpdateService;
    private ProfileRepository $eventRepository;

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
        $event = new Profile();
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
        $this->eventRepository = $this->createMock(ProfileRepository::class);

        $this->eventUpdateService = new ProfileUpdateService($this->eventRepository);
    }
}
