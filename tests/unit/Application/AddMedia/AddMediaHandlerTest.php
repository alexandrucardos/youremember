<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\AddMedia;

use App\Application\AddMedia\AddMediaCommand;
use App\Application\AddMedia\AddMediaHandler;
use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\Domain\Model\Event\Exception\MissingFiles;
use App\Domain\Model\Event\Message\EventNotValidException;
use App\Domain\Model\Event\Message\IncorrectMimeTypeException;
use App\Domain\Model\Event\Message\MaximumEventItemsReachedException;
use App\ValueObject\HashValueObject;
use App\ValueObject\Status;
use App\ValueObject\UserRole;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AddMediaHandlerTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';
    private const FOLDER = 'client';

    private EventRepositoryInterface&MockObject $eventRepository;
    private AddMediaHandler $handler;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepositoryInterface::class);
        $this->handler = new AddMediaHandler($this->eventRepository);
    }

    public static function emptyUserIdentifierDataProvider(): array
    {
        return [
            'empty string' => ['userIdentifier' => ''],
            'whitespace' => ['userIdentifier' => '   ']
        ];
    }

    public function testInvokeThrowsMissingFilesWhenFilesAreEmpty(): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::VALID]);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([100, 0]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn([]);

        $this->expectException(MissingFiles::class);
        $this->expectExceptionMessage('Missing files from request');

        ( $this->handler )($this->buildCommand([]));
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenGetEventUuidAndStatusReturnsEmptyArray(): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([]);
        $this->eventRepository->expects($this->never())->method('getExistingMediaInfo');

        $this->expectException(EventNotFoundException::class);

        ( $this->handler )($this->buildCommand([$this->createMock(UploadedFile::class)]));
    }

    public function testInvokeThrowsEventNotFoundExceptionWhenEventUuidIsNull(): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([null, null]);
        $this->eventRepository->expects($this->never())->method('getExistingMediaInfo');

        $this->expectException(EventNotFoundException::class);

        ( $this->handler )($this->buildCommand([$this->createMock(UploadedFile::class)]));
    }

    public function testInvokeThrowsEventNotValidExceptionWhenStatusIsInvalid(): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::INVALID]);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([100, 0]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn(['image/jpeg']);

        $this->expectException(EventNotValidException::class);

        ( $this->handler )($this->buildCommand([$this->createMock(UploadedFile::class)]));
    }

    public static function maximumItemsDataProvider(): array
    {
        return [
            'over limit' => ['maxItems' => 10, 'existingItems' => 10, 'newFiles' => 1],
            'multiple files over' => ['maxItems' => 5, 'existingItems' => 3, 'newFiles' => 3]
        ];
    }

    /**
     * @dataProvider maximumItemsDataProvider
     */
    public function testInvokeThrowsMaximumMediaItemsReachedWhenLimitExceeded(
        int $maxItems,
        int $existingItems,
        int $newFiles
    ): void {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::VALID]);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([$maxItems, $existingItems]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn(['image/jpeg']);
        $this->eventRepository->expects($this->never())->method('saveMediaFiles');

        $this->expectException(MaximumEventItemsReachedException::class);

        $files = array_fill(0, $newFiles, $this->createMock(UploadedFile::class));
        ( $this->handler )($this->buildCommand($files));
    }

    public static function unsupportedMimeTypesDataProvider(): array
    {
        return [
            'pdf' => [['application/pdf']],
            'svg' => [['image/svg+xml']],
            'mixed' => [['image/jpeg', 'application/pdf']]
        ];
    }

    /**
     * @dataProvider unsupportedMimeTypesDataProvider
     */
    public function testInvokeThrowsIncorrectMimeTypeExceptionForUnsupportedTypes(array $mimeTypes): void
    {
        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::VALID]);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([100, 0]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn($mimeTypes);
        $this->eventRepository->expects($this->never())->method('saveMediaFiles');

        $this->expectException(IncorrectMimeTypeException::class);

        $files = array_fill(0, count($mimeTypes), $this->createMock(UploadedFile::class));
        ( $this->handler )($this->buildCommand($files));
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

    /**
     * @dataProvider acceptedMimeTypesDataProvider
     */
    public function testInvokeCallsSaveMediaFilesForAcceptedMimeTypes(array $mimeTypes): void
    {
        $files = array_fill(0, count($mimeTypes), $this->createMock(UploadedFile::class));

        $this->eventRepository->method('getExistingEventUuidAndStatus')->willReturn([self::UUID, Status::VALID]);
        $this->eventRepository->method('getExistingMediaInfo')->willReturn([100, 0]);
        $this->eventRepository->method('getUniqueMimeTypes')->willReturn($mimeTypes);

        $this->eventRepository
            ->expects($this->once())
            ->method('saveMediaFiles')
            ->with(
                $this->callback(
                    static fn(EventEntity $entity): bool => $entity->eventUuidValueObject->value === self::UUID
                ),
                AddMediaHandler::MAX_IMAGE_SIZE_BYTES,
                AddMediaHandler::MAX_TOTAL_DEMO_SIZE_BYTES
            );

        ( $this->handler )($this->buildCommand($files));
    }

    private function buildCommand(array $files, ?UuidValueObject $uuid = null): AddMediaCommand
    {
        return new AddMediaCommand(
            userRole: UserRole::ROLE_ADMIN,
            userIdentifier: new HashValueObject(self::FOLDER),
            eventUuidValueObject: $uuid ?? new UuidValueObject(self::UUID),
            files: $files
        );
    }
}
