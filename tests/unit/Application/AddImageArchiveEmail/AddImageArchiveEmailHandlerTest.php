<?php

namespace App\Tests\unit\Application\AddImageArchiveEmail;

use App\Application\AddImageArchiveEmail\AddImageArchiveEmailCommand;
use App\Application\AddImageArchiveEmail\AddImageArchiveEmailHandler;
use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;

class AddImageArchiveEmailHandlerTest extends TestCase
{
    public static function validCommandDataProvider(): array
    {
        return [
            'standard email and uuid' => [
                'email' => 'user@example.com',
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            ]
        ];
    }

    /**
     * @dataProvider validCommandDataProvider
     */
    public function testInvokeSavesImageArchiveEntity(string $email, string $uuid): void
    {
        $eventRepositoryInterface = $this->createMock(EventRepositoryInterface::class);
        $eventRepositoryInterface
            ->expects($this->once())
            ->method('saveArchiveEmailForEvent')
            ->with($this->callback(function (EventEntity $eventEntity) use ($email, $uuid) {
                return $eventEntity->getImageArchiveEmail()->value === $email
                    && $eventEntity->eventUuidValueObject->value === $uuid;
            }));

        $command = new AddImageArchiveEmailCommand(
            emailValueObject: new EmailValueObject($email),
            uuidValueObject: new UuidValueObject($uuid),
        );

        $handler = new AddImageArchiveEmailHandler($eventRepositoryInterface);
        $handler($command);
    }

    public function testInvokePropagatesEventNotFoundException(): void
    {
        $eventRepositoryInterface = $this->createMock(EventRepositoryInterface::class);
        $eventRepositoryInterface
            ->expects($this->once())
            ->method('saveArchiveEmailForEvent')
            ->willThrowException(new EventNotFoundException());

        $command = new AddImageArchiveEmailCommand(
            uuidValueObject: new UuidValueObject('550e8400-e29b-41d4-a716-446655440000'),
            emailValueObject: new EmailValueObject('user@example.com'),
        );

        $this->expectException(EventNotFoundException::class);

        $handler = new AddImageArchiveEmailHandler($eventRepositoryInterface);
        $handler($command);
    }
}