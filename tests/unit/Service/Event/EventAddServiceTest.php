<?php

declare(strict_types = 1);

namespace App\Tests\unit\Service\Event;

use App\Entity\Profile;
use App\Entity\User;
use App\Exception\User\NotFoundException;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use App\Service\Event\EventAddService;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

class EventAddServiceTest extends TestCase
{
    public static function successfulAddDataProvider(): array
    {
        return [
            'standard user with regular order' => [
                'email' => 'test@example.com',
                'orderId' => 123
            ]
        ];
    }

    public static function userNotFoundDataProvider(): array
    {
        return [
            'nonexistent user' => [
                'email' => 'nonexistent@example.com',
                'orderId' => 456
            ]
        ];
    }

    /**
     * @dataProvider successfulAddDataProvider
     */
    public function testAddCreatesEventSuccessfully(string $email, int $orderId): void
    {
        $user = new User();

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findBy')->with(['email' => $email])->willReturn([$user]);

        $eventRepository = $this->createMock(ProfileRepository::class);
        $eventRepository->expects($this->once())->method('save')->with($this->isInstanceOf(Profile::class));

        $service = new EventAddService($eventRepository, $userRepository);

        $event = $service->add(new EmailValueObject($email), new OrderIdValueObject($orderId));

        $this->assertInstanceOf(Profile::class, $event);
        $this->assertNotNull($event->getExternalId());
        $this->assertIsInt($event->getExternalId());
        $this->assertGreaterThanOrEqual(100000, $event->getExternalId());
        $this->assertLessThanOrEqual(999999, $event->getExternalId());
        $this->assertSame($orderId, $event->getOrderId());
        $this->assertSame($user, $event->getUser());
    }

    /**
     * @dataProvider userNotFoundDataProvider
     */
    public function testAddThrowsNotFoundExceptionWhenUserNotFound(string $email, int $orderId): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findBy')->with(['email' => $email])->willReturn([]);

        $eventRepository = $this->createMock(ProfileRepository::class);
        $eventRepository->expects($this->never())->method('save');

        $service = new EventAddService($eventRepository, $userRepository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $service->add(new EmailValueObject($email), new OrderIdValueObject($orderId));
    }
}
