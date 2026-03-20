<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\InitiateMultipartMediaUpload;

use App\Application\InitiateMultipartMediaUpload\InitiateMediaUploadCommand;
use App\Application\InitiateMultipartMediaUpload\InitiateMediaUploadHandler;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\ValueObject\EmailValueObject;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InitiateMediaUploadHandlerTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';
    private const ORDER_ID = 123;
    private const USER_EMAIL = 'user@example.com';
    private const FILENAME = 'test-video.mp4';
    private const MIME_TYPE = 'video/mp4';

    private ProfileRepositoryInterface&MockObject $eventRepository;
    private InitiateMediaUploadHandler $handler;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->handler = new InitiateMediaUploadHandler($this->eventRepository);
    }

    public function testInvokeThrowsProfileNotFoundExceptionWhenProfileUuidIsNull(): void
    {
        $this->eventRepository->method('getExistingProfileUuidForOrderIdAndEmail')->willReturn(null);
        $this->eventRepository->expects($this->never())->method('fetchMultipartInitData');

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage('Profile not found');

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeThrowsProfileNotFoundExceptionWhenProfileUuidIsEmpty(): void
    {
        $this->eventRepository->method('getExistingProfileUuidForOrderIdAndEmail')->willReturn('');
        $this->eventRepository->expects($this->never())->method('fetchMultipartInitData');

        $this->expectException(ProfileNotFoundException::class);
        $this->expectExceptionMessage('Profile not found');

        ( $this->handler )($this->buildCommand());
    }

    public function testInvokeReturnsMultipartInitDataWhenProfileExists(): void
    {
        $expectedInitData = [
            'uploadId' => 'test-upload-id-123',
            'key' => 'profiles/' . self::UUID . '/test-video.mp4'
        ];

        $this->eventRepository->method('getExistingProfileUuidForOrderIdAndEmail')->willReturn(self::UUID);

        $this->eventRepository
            ->expects($this->once())
            ->method('fetchMultipartInitData')
            ->willReturn($expectedInitData);

        $result = ( $this->handler )($this->buildCommand());

        $this->assertSame($expectedInitData, $result);
    }

    private function buildCommand(): InitiateMediaUploadCommand
    {
        return new InitiateMediaUploadCommand(
            orderIdValueObject: new OrderIdValueObject(self::ORDER_ID),
            userEmail: new EmailValueObject(self::USER_EMAIL),
            filename: self::FILENAME,
            mimeType: self::MIME_TYPE
        );
    }
}
