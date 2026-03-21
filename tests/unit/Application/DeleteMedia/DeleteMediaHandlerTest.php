<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\DeleteMedia;

use App\Application\DeleteMedia\DeleteMediaCommand;
use App\Application\DeleteMedia\DeleteMediaHandler;
use App\Domain\Model\Profile\Exception\MissingFiles;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteMediaHandlerTest extends TestCase
{
    private const PROFILE_ID = 1;
    private const ORDER_ID = 123;
    private const USER_EMAIL = 'user@example.com';

    private ProfileRepositoryInterface&MockObject $eventRepository;
    private DeleteMediaHandler $handler;

    public static function filePathsDataProvider(): array
    {
        return [
            'single file' => [['12345/client/image.jpg']],
            'multiple files' => [['12345/client/image.jpg', '12345/client/video.mp4']]
        ];
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenEventUuidIsNull(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn(null);
        $this->eventRepository->expects($this->never())->method('deleteMediaFiles');

        $this->expectException(ProfileNotFoundException::class);

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenEventUuidIsEmpty(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn('');
        $this->eventRepository->expects($this->never())->method('deleteMediaFiles');

        $this->expectException(ProfileNotFoundException::class);

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeThrowsMissingFilesWhenFilePathsAreEmpty(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn((string) self::PROFILE_ID);
        $this->eventRepository->expects($this->never())->method('deleteMediaFiles');

        $this->expectException(MissingFiles::class);

        ( $this->handler )($this->buildCommand([]));
    }

    /**
     * @dataProvider filePathsDataProvider
     */
    public function testInvokeCallsDeleteMediaFiles(array $filePaths): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn((string) self::PROFILE_ID);

        $this->eventRepository
            ->expects($this->once())
            ->method('deleteMediaFiles')
            ->with($this->callback(
                static fn(ProfileEntity $eventEntity): bool => (
                    $eventEntity->profileIdValueObject->value === self::PROFILE_ID
                )
            ));

        ( $this->handler )($this->buildCommand($filePaths));
    }

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->handler = new DeleteMediaHandler($this->eventRepository);
    }

    private function buildCommand(array $filePaths = ['some/path/file.jpg']): DeleteMediaCommand
    {
        return new DeleteMediaCommand(
            orderIdValueObject: new OrderIdValueObject(self::ORDER_ID),
            filePaths: $filePaths,
            userEmail: new EmailValueObject(self::USER_EMAIL)
        );
    }
}
