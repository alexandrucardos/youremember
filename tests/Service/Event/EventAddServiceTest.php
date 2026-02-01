<?php

namespace App\Tests\Service\Event;

use App\Entity\Event;
use App\Entity\User;
use App\Exception\User\NotFoundException;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\Event\EventAddService;
use App\ValueObject\EmailValueObject;
use App\ValueObject\Event\EventAddValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

class EventAddServiceTest extends TestCase
{
    public function testAddCreatesEventSuccessfully(): void
    {
        $user = new User();

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn([$user]);

        $eventRepository = $this->createMock(EventRepository::class);
        $eventRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Event::class));

        $service = new EventAddService($eventRepository, $userRepository);

        $valueObject = new EventAddValueObject(
            new EmailValueObject('test@example.com'),
            new OrderIdValueObject(123)
        );

        $event = $service->add($valueObject);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertNotNull($event->getUuid());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $event->getUuid()
        );
        $this->assertSame(123, $event->getOrderId());
    }

    public function testAddThrowsNotFoundExceptionWhenUserNotFound(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn([]);

        $eventRepository = $this->createMock(EventRepository::class);
        $eventRepository
            ->expects($this->never())
            ->method('save');

        $service = new EventAddService($eventRepository, $userRepository);

        $valueObject = new EventAddValueObject(
            new EmailValueObject('nonexistent@example.com'),
            new OrderIdValueObject(456)
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $service->add($valueObject);
    }
}