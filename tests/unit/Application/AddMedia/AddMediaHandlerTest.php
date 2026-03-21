<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\AddMedia;

use App\Application\AddMedia\AddMediaCommand;
use App\Application\AddMedia\AddMediaHandler;
use App\Domain\Model\Profile\Exception\MissingFiles;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\Message\IncorrectMimeTypeException;
use App\Domain\Model\Profile\Message\MaximumProfileItemsReachedException;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AddMediaHandlerTest extends TestCase
{
    private const PROFILE_ID = 1;
    private const ORDER_ID = 123;
    private const USER_EMAIL = 'user@example.com';

    private ProfileRepositoryInterface&MockObject $eventRepository;
    private AddMediaHandler $handler;

    public static function maximumItemsDataProvider(): array
    {
        return [
            'over limit' => ['maxItems' => 10, 'existingItems' => 10, 'newFiles' => 1],
            'multiple files over' => ['maxItems' => 5, 'existingItems' => 3, 'newFiles' => 3]
        ];
    }

    public static function unsupportedMimeTypesDataProvider(): array
    {
        return [
            'pdf' => [['application/pdf']],
            'svg' => [['image/svg+xml']],
            'mixed' => [['image/jpeg', 'application/pdf']]
        ];
    }

    public static function acceptedMimeTypesDataProvider(): array
    {
        return [
            'jpeg images' => [['image/jpeg']],
            'png images' => [['image/png']],
            'gif images' => [['image/gif']],
            'mp4 video' => [['video/mp4']],
            'webm video' => [['video/webm']],
            'mixed valid' => [['image/jpeg', 'video/mp4']]
        ];
    }

    public function testInvokeThrowsMissingFilesWhenFilesAreEmpty(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn((string) self::PROFILE_ID);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([100, 0]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn([]);

        $this->expectException(MissingFiles::class);
        $this->expectExceptionMessage('Missing files from request');

        ( $this->handler )($this->buildCommand([]));
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenEventUuidIsNull(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn(null);
        $this->eventRepository->expects($this->never())->method('getExistingMediaInfo');

        $this->expectException(ProfileNotFoundException::class);

        ( $this->handler )($this->buildCommand([$this->createMock(UploadedFile::class)]));
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenEventUuidIsEmpty(): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn('');
        $this->eventRepository->expects($this->never())->method('getExistingMediaInfo');

        $this->expectException(ProfileNotFoundException::class);

        ( $this->handler )($this->buildCommand([$this->createMock(UploadedFile::class)]));
    }

    /**
     * @dataProvider maximumItemsDataProvider
     */
    public function testInvokeThrowsMaximumMediaItemsReachedWhenLimitExceeded(
        int $maxItems,
        int $existingItems,
        int $newFiles
    ): void {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn((string) self::PROFILE_ID);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([$maxItems, $existingItems]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn(['image/jpeg']);
        $this->eventRepository->expects($this->never())->method('saveMediaFiles');

        $this->expectException(MaximumProfileItemsReachedException::class);

        $files = array_fill(0, $newFiles, $this->createMock(UploadedFile::class));
        ( $this->handler )($this->buildCommand($files));
    }

    /**
     * @dataProvider unsupportedMimeTypesDataProvider
     */
    public function testInvokeThrowsIncorrectMimeTypeExceptionForUnsupportedTypes(array $mimeTypes): void
    {
        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn((string) self::PROFILE_ID);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([100, 0]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn($mimeTypes);
        $this->eventRepository->expects($this->never())->method('saveMediaFiles');

        $this->expectException(IncorrectMimeTypeException::class);

        $files = array_fill(0, count($mimeTypes), $this->createMock(UploadedFile::class));
        ( $this->handler )($this->buildCommand($files));
    }

    /**
     * @dataProvider acceptedMimeTypesDataProvider
     */
    public function testInvokeCallsSaveMediaFilesForAcceptedMimeTypes(array $mimeTypes): void
    {
        $files = array_fill(0, count($mimeTypes), $this->createMock(UploadedFile::class));

        $this->eventRepository->method('getExistingProfileIdForOrderIdAndEmail')->willReturn((string) self::PROFILE_ID);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([100, 0]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn($mimeTypes);

        $this->eventRepository
            ->expects($this->once())
            ->method('saveMediaFiles')
            ->with(
                $this->callback(
                    static fn(ProfileEntity $entity): bool => $entity->profileIdValueObject->value === self::PROFILE_ID
                ),
                AddMediaHandler::MAX_IMAGE_SIZE_BYTES
            );

        ( $this->handler )($this->buildCommand($files));
    }

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->handler = new AddMediaHandler($this->eventRepository);
    }

    private function buildCommand(array $files): AddMediaCommand
    {
        return new AddMediaCommand(
            orderIdValueObject: new OrderIdValueObject(self::ORDER_ID),
            files: $files,
            userEmail: new EmailValueObject(self::USER_EMAIL)
        );
    }
}
