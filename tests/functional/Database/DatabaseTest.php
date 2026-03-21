<?php

declare(strict_types = 1);

namespace App\Tests\functional\Database;

use App\Domain\ValueObject\Status;
use App\Domain\ValueObject\UserRole;
use App\Entity\Profile;
use App\Entity\User;
use App\Tests\functional\FunctionalTestBase;

class DatabaseTest extends FunctionalTestBase
{
    public function testCanPersistAndRetrieveUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com')->setRole(UserRole::ROLE_CLIENT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->entityManager->clear();

        $retrievedUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

        self::assertNotNull($retrievedUser);
        self::assertEquals('test@example.com', $retrievedUser->getEmail());
        self::assertEquals(UserRole::ROLE_CLIENT, $retrievedUser->getRole());
    }

    public function testCanPersistAndRetrieveProfile(): void
    {
        $user = new User();
        $user->setEmail('client@example.com')->setRole(UserRole::ROLE_CLIENT);

        $profile = new Profile();
        $profile
            ->setExternalId(123)
            ->setOrderId(123)
            ->setName('Test Profile')
            ->setNameFont('classic')
            ->setStatus(Status::VALID)
            ->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $this->entityManager->clear();

        $retrievedProfile = $this->entityManager->getRepository(Profile::class)->findOneBy(['order_id' => 123]);

        self::assertNotNull($retrievedProfile);
        self::assertEquals('Test Profile', $retrievedProfile->getName());
        self::assertEquals(123, $retrievedProfile->getExternalId());
        self::assertEquals(Status::VALID, $retrievedProfile->getStatus());
        self::assertEquals('client@example.com', $retrievedProfile->getUser()->getEmail());
    }

    public function testDatabaseIsolationBetweenTests(): void
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $profiles = $this->entityManager->getRepository(Profile::class)->findAll();

        self::assertCount(0, $users, 'Database should be empty at test start');
        self::assertCount(0, $profiles, 'Database should be empty at test start');
    }

    public function testCanUpdateEntity(): void
    {
        $user = new User();
        $user->setEmail('original@example.com')->setRole(UserRole::ROLE_CLIENT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        $user->setEmail('updated@example.com');
        $this->entityManager->flush();

        $this->entityManager->clear();

        $retrievedUser = $this->entityManager->getRepository(User::class)->find($userId);

        self::assertNotNull($retrievedUser);
        self::assertEquals('updated@example.com', $retrievedUser->getEmail());
    }

    public function testCanDeleteEntity(): void
    {
        $user = new User();
        $user->setEmail('delete@example.com')->setRole(UserRole::ROLE_CLIENT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->entityManager->clear();

        $retrievedUser = $this->entityManager->getRepository(User::class)->find($userId);

        self::assertNull($retrievedUser);
    }

    public function testProfileUserRelationship(): void
    {
        $user = new User();
        $user->setEmail('relationship@example.com')->setRole(UserRole::ROLE_CLIENT);

        $profile1 = new Profile();
        $profile1
            ->setExternalId(101)
            ->setOrderId(101)
            ->setName('Profile 1')
            ->setNameFont('classic')
            ->setStatus(Status::VALID)
            ->setUser($user);

        $profile2 = new Profile();
        $profile2
            ->setExternalId(102)
            ->setOrderId(102)
            ->setName('Profile 2')
            ->setNameFont('modern')
            ->setStatus(Status::VALID)
            ->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($profile1);
        $this->entityManager->persist($profile2);
        $this->entityManager->flush();

        $this->entityManager->clear();

        $retrievedUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'relationship@example.com']);

        self::assertNotNull($retrievedUser);
        self::assertCount(2, $retrievedUser->getEvents());

        $profileNames = array_map(fn($profile) => $profile->getName(), $retrievedUser->getEvents()->toArray());
        self::assertContains('Profile 1', $profileNames);
        self::assertContains('Profile 2', $profileNames);
    }
}
