<?php

namespace App\Tests\unit\Domain\Model\Event;

use App\Domain\Model\Event\EventEntity;
use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;

class EventEntityTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    public function testConstructorStoresUuid(): void
    {
        $uuid = new UuidValueObject(self::UUID);

        $entity = new EventEntity(eventUuidValueObject: $uuid);

        $this->assertSame($uuid, $entity->eventUuidValueObject);
    }

    public function testSetAndGetFeedbackValueObject(): void
    {
        $entity = new EventEntity(new UuidValueObject(self::UUID));
        $feedback = new FeedbackValueObject('Great event!');

        $entity->setFeedbackValueObject($feedback);

        $this->assertSame($feedback, $entity->getFeedbackValueObject());
    }

    public function testSetAndGetImageArchiveEmail(): void
    {
        $entity = new EventEntity(new UuidValueObject(self::UUID));
        $email = new EmailValueObject('user@example.com');

        $entity->setImageArchiveEmail($email);

        $this->assertSame($email, $entity->getImageArchiveEmail());
    }
}
