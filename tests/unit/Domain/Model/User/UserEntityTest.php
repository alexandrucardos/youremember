<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\Model\User;

use App\Domain\Model\User\UserEntity;
use App\Domain\ValueObject\EmailValueObject;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    public function testConstructorStoresEmailValueObject(): void
    {
        $email = new EmailValueObject('user@example.com');

        $entity = new UserEntity($email);

        $this->assertSame($email, $entity->emailValueObject);
    }

    public function testAdminUserIdentifierIsClient(): void
    {
        $this->assertSame('client', UserEntity::ADMIN_USER_IDENTIFIER);
    }
}
