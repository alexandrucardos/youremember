<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\UpdateProfileNameAndTitle;

use App\Application\UpdateProfileName\UpdateProfileNameCommand;
use App\Application\UpdateProfileName\UpdateProfileNameHandler;
use App\Application\UpdateProfileNameAndTitle\UpdateProfileNameAndTitleCommand;
use App\Application\UpdateProfileNameAndTitle\UpdateProfileNameAndTitleHandler;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;
use App\Domain\ValueObject\ProfileNameValueObject;
use App\Domain\ValueObject\ProfileTitleValueObject;
use App\Service\Tools\TranslatorMock;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateProfileNameAndTitleHandlerTest extends TestCase
{
    private const PROFILE_ID = 1;
    private const ORDER_ID = 123;
    private const USER_EMAIL = 'user@example.com';

    public static function validProfileDataProvider(): array
    {
        return [
            'profile with custom name and font' => [
                'profile_name' => 'john',
                'profile_font' => 'elegant',
                'profile_title' => null
            ],
            'profile with custom name title and font' => [
                'name' => 'doe',
                'font' => 'modern',
                'profile_title' => 'title'
            ]
        ];
    }

    /**
     * @dataProvider validProfileDataProvider
     */
    public function testInvokeUpdatesProfileNameFontAndTitle(
        string $profileName,
        string $profileFont,
        ?string $profileTitle
    ): void {
        $profileRepository = $this->createMock(ProfileRepositoryInterface::class);
        $profileRepository
            ->expects($this->once())
            ->method('getExistingProfileIdForOrderIdAndEmail')
            ->willReturn((string) self::PROFILE_ID);

        $translator = new TranslatorMock();

        $profileRepository
            ->expects($this->once())
            ->method('updateProfileNameTitleAndFont')
            ->with($this->callback(
                static fn(ProfileEntity $profileEntity): bool => (
                    $profileEntity->profileIdValueObject->value === self::PROFILE_ID
                    && $profileEntity->getProfileName()->value === $profileName
                    && $profileEntity->getProfileNameFont()->value === $profileFont
                    && $profileEntity->getTitle()->value
                    === ( $profileTitle ?? $translator->trans('profile.title.default') )
                )
            ));

        $command = new UpdateProfileNameAndTitleCommand(
            new OrderIdValueObject(self::ORDER_ID),
            new EmailValueObject(self::USER_EMAIL),
            new ProfileNameValueObject($profileName, $translator),
            new ProfileNameFontValueObject($profileFont),
            new ProfileTitleValueObject($profileTitle, $translator)
        );

        $handler = new UpdateProfileNameAndTitleHandler($profileRepository);
        $handler($command);
    }

    public function testInvokeThrowsProfileNotFoundExceptionWhenUuidIsNull(): void
    {
        $profileRepository = $this->createMock(ProfileRepositoryInterface::class);
        $profileRepository->expects($this->once())->method('getExistingProfileIdForOrderIdAndEmail')->willReturn(null);

        $profileRepository->expects($this->never())->method('updateProfileNameAndFont');

        $this->expectException(ProfileNotFoundException::class);

        $command = new UpdateProfileNameCommand(
            new OrderIdValueObject(self::ORDER_ID),
            new EmailValueObject(self::USER_EMAIL),
            new ProfileNameValueObject('My Wedding', $this->createStub(TranslatorInterface::class)),
            new ProfileNameFontValueObject('elegant')
        );

        $handler = new UpdateProfileNameHandler($profileRepository);
        $handler($command);
    }

    public function testInvokeThrowsProfileNotFoundExceptionWhenUuidIsEmpty(): void
    {
        $profileRepository = $this->createMock(ProfileRepositoryInterface::class);
        $profileRepository->expects($this->once())->method('getExistingProfileIdForOrderIdAndEmail')->willReturn('');

        $profileRepository->expects($this->never())->method('updateProfileNameAndFont');

        $this->expectException(ProfileNotFoundException::class);

        $command = new UpdateProfileNameCommand(
            new OrderIdValueObject(self::ORDER_ID),
            new EmailValueObject(self::USER_EMAIL),
            new ProfileNameValueObject('My Wedding', $this->createStub(TranslatorInterface::class)),
            new ProfileNameFontValueObject('elegant')
        );

        $handler = new UpdateProfileNameHandler($profileRepository);
        $handler($command);
    }
}
