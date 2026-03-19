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
use App\ValueObject\Event\EventAddValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

class EventAddServiceTest extends TestCase
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

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

        $valueObject = new EventAddValueObject(new EmailValueObject($email), new OrderIdValueObject($orderId));

        $event = $service->add($valueObject);

        $this->assertInstanceOf(Profile::class, $event);
        $this->assertNotNull($event->getUuid());
        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $event->getUuid());
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

        $valueObject = new EventAddValueObject(new EmailValueObject($email), new OrderIdValueObject($orderId));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $service->add($valueObject);
    }
}
