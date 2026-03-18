<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\AddProfile;

use App\Application\AddProfile\AddProfileCommand;
use App\Application\AddProfile\AddProfileHandler;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Service\UuidInterface;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

class AddProfileHandlerTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

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
        $uuidService = $this->createMock(UuidInterface::class);
        $uuidService->method('generate')->willReturn(self::UUID);

        $eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $eventRepository
            ->expects($this->once())
            ->method('saveProfile')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity) => (
                    $eventEntity->getOrderId()->value === $orderId
                    && $eventEntity->profileUuidValueObject->value === self::UUID
                )
            ));

        $command = new AddProfileCommand(
            orderIdValueObject: new OrderIdValueObject($orderId),
            emailValueObject: new EmailValueObject($email)
        );

        $handler = new AddProfileHandler($eventRepository, $uuidService);
        $handler($command);
    }
}
