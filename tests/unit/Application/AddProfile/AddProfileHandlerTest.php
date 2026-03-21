<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\AddProfile;

use App\Application\AddProfile\AddProfileCommand;
use App\Application\AddProfile\AddProfileHandler;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use App\ValueObject\ProfileIdValueObject;
use PHPUnit\Framework\TestCase;

class AddProfileHandlerTest extends TestCase
{
    private const PROFILE_ID = 1;

    public static function validProfileDataProvider(): array
    {
        return [
            'standard order' => [
                'order_id' => 1,
                'email' => 'aa@b.com'
            ]
        ];
    }

    /**
     * @dataProvider validProfileDataProvider
     */
    public function testInvokeSavesProfile(
        int $orderId,
        string $email
    ): void {
        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository->method('getExistingProfileId')->willReturn(null);
        $eventRepository
            ->expects($this->once())
            ->method('saveProfile')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity) => (
                    $eventEntity->getOrderId()->value === $orderId
                    && $eventEntity->profileIdValueObject->value === self::PROFILE_ID
                )
            ));

        $command = new AddProfileCommand(
            orderIdValueObject: new OrderIdValueObject($orderId),
            emailValueObject: new EmailValueObject($email),
            profileIdValueObject: new ProfileIdValueObject(self::PROFILE_ID)
        );

        $handler = new AddProfileHandler($eventRepository);
        $handler($command);
    }
}
