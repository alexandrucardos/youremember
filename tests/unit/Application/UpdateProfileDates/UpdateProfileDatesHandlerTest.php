<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\UpdateProfileDates;

use App\Application\UpdateProfileDates\UpdateProfileDatesCommand;
use App\Application\UpdateProfileDates\UpdateProfileDatesHandler;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\DateValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateProfileDatesHandlerTest extends TestCase
{
    private const PROFILE_ID = 1;
    private const ORDER_ID = 123;
    private const USER_EMAIL = 'user@example.com';
    private const BORN_AT = '1990-01-01';
    private const DEPARTED_AT = '2023-12-31';

    private ProfileRepositoryInterface&MockObject $profileRepository;
    private UpdateProfileDatesHandler $handler;

    public function testInvokeThrowsProfileNotFoundExceptionWhenProfileUuidIsNull(): void
    {
        $this->profileRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn(null);
        $this->profileRepository->expects($this->never())->method('updateProfileDates');

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage('Profile not found');

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeThrowsProfileNotFoundExceptionWhenProfileUuidIsEmpty(): void
    {
        $this->profileRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn('');
        $this->profileRepository->expects($this->never())->method('updateProfileDates');

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage('Profile not found');

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeUpdatesProfileDatesWhenProfileExists(): void
    {
        $this->profileRepository
            ->method('getExistingProfileIdForOrderIdAndEmail')
            ->willReturn((string) self::PROFILE_ID);

        $this->profileRepository
            ->expects($this->once())
            ->method('updateProfileDates')
            ->with($this->callback(
                static fn(ProfileEntity $entity): bool => $entity->profileIdValueObject->value === self::PROFILE_ID
            ));

        ( $this->handler )($this->buildCommand());
    }

    protected function setUp(): void
    {
        $this->profileRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->handler = new UpdateProfileDatesHandler($this->profileRepository);
    }

    private function buildCommand(): UpdateProfileDatesCommand
    {
        return new UpdateProfileDatesCommand(
            orderIdValueObject: new OrderIdValueObject(self::ORDER_ID),
            userEmail: new EmailValueObject(self::USER_EMAIL),
            bornAtValueObject: new DateValueObject(self::BORN_AT),
            departedAtValueObject: new DateValueObject(self::DEPARTED_AT)
        );
    }
}
