<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\UpdateProfileObituary;

use App\Application\UpdateProfileObituary\UpdateProfileObituaryCommand;
use App\Application\UpdateProfileObituary\UpdateProfileObituaryHandler;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\EmailValueObject;
use App\ValueObject\ObituaryValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateProfileObituaryHandlerTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';
    private const ORDER_ID = 123;
    private const USER_EMAIL = 'user@example.com';
    private const OBITUARY = 'A loving memory of a wonderful person who touched many lives.';

    private ProfileRepositoryInterface&MockObject $profileRepository;
    private UpdateProfileObituaryHandler $handler;

    public function testInvokeThrowsProfileNotFoundExceptionWhenProfileUuidIsNull(): void
    {
        $this->profileRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn(null);
        $this->profileRepository->expects($this->never())->method('updateProfileObituary');

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage('Profile not found');

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeThrowsProfileNotFoundExceptionWhenProfileUuidIsEmpty(): void
    {
        $this->profileRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn('');
        $this->profileRepository->expects($this->never())->method('updateProfileObituary');

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage('Profile not found');

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeUpdatesProfileObituaryWhenProfileExists(): void
    {
        $this->profileRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn(self::UUID);

        $this->profileRepository
            ->expects($this->once())
            ->method('updateProfileObituary')
            ->with($this->callback(
                static fn(ProfileEntity $entity): bool => $entity->profileIdValueObject->value === self::UUID
            ));

        ( $this->handler )($this->buildCommand());
    }

    protected function setUp(): void
    {
        $this->profileRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->handler = new UpdateProfileObituaryHandler($this->profileRepository);
    }

    private function buildCommand(): UpdateProfileObituaryCommand
    {
        return new UpdateProfileObituaryCommand(
            orderIdValueObject: new OrderIdValueObject(self::ORDER_ID),
            userEmail: new EmailValueObject(self::USER_EMAIL),
            obituaryValueObject: new ObituaryValueObject(self::OBITUARY)
        );
    }
}
