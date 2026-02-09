<?php

namespace App\Tests\unit\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\User\UserAddService;
use App\ValueObject\EmailValueObject;
use App\ValueObject\User\UserAddValueObject;
use App\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

class UserAddServiceTest extends TestCase
{
    /**
     * @dataProvider existingUserDataProvider
     */
    public function testAddReturnsExistingUserWhenEmailExists(string $email, UserRole $role): void
    {
        $existingUser = new User();
        $existingUser->setEmail($email);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($existingUser);

        $userRepository
            ->expects($this->never())
            ->method('save');

        $userAddService = new UserAddService($userRepository);

        $userAddValueObject = new UserAddValueObject(
            new EmailValueObject($email),
            $role
        );

        $result = $userAddService->add($userAddValueObject);

        $this->assertSame($existingUser, $result);
    }

    /**
     * @dataProvider newUserDataProvider
     */
    public function testAddCreatesNewUserWhenEmailNotExists(string $email, UserRole $role): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        $userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $userAddService = new UserAddService($userRepository);

        $userAddValueObject = new UserAddValueObject(
            new EmailValueObject($email),
            $role
        );

        $result = $userAddService->add($userAddValueObject);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($email, $result->getEmail());
        $this->assertSame($role, $result->getRole());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getModifiedAt());
    }

    public function testAddSetsCreatedAtAndModifiedAtToSameTime(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $userAddService = new UserAddService($userRepository);

        $userAddValueObject = new UserAddValueObject(
            new EmailValueObject('test@example.com'),
            UserRole::ROLE_GUEST
        );

        $result = $userAddService->add($userAddValueObject);

        $this->assertEquals(
            $result->getEmail(),
            $userAddValueObject->email->value
        );
    }

    public static function existingUserDataProvider(): array
    {
        return [
            'existing client user' => [
                'email' => 'client@example.com',
                'role' => UserRole::ROLE_CLIENT,
            ],
        ];
    }

    public static function newUserDataProvider(): array
    {
        return [
            'new client user' => [
                'email' => 'newclient@example.com',
                'role' => UserRole::ROLE_CLIENT,
            ],
            'new guest user' => [
                'email' => 'newguest@example.com',
                'role' => UserRole::ROLE_GUEST,
            ],
        ];
    }
}