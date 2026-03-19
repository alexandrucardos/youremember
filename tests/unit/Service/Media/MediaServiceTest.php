<?php

declare(strict_types = 1);

namespace App\Tests\unit\Service\Media;

use App\Entity\Media;
use App\Entity\Profile;
use App\Exception\Media\NotFoundException;
use App\Repository\MediaRepository;
use App\Repository\ProfileRepository;
use App\Service\Bucket\BucketProviderInterface;
use App\Service\Media\MediaService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaServiceTest extends TestCase
{
    private MediaService $mediaService;
    private MediaRepository $mediaRepository;
    private ProfileRepository $eventRepository;
    private BucketProviderInterface $bucketProvider;

    public static function buildUrlDataProvider(): array
    {
        return [
            'simple prefix' => [
                'prefix' => '123/client/photo.jpg',
                'expectedUrl' => 'https://test-bucket.s3.eu-west-1.amazonaws.com/123/client/photo.jpg'
            ],
            'prefix with spaces' => [
                'prefix' => '123/client/photo with spaces.jpg',
                'expectedUrl' => 'https://test-bucket.s3.eu-west-1.amazonaws.com/123/client/photo with spaces.jpg'
            ]
        ];
    }

    public static function appleExtensionsDataProvider(): array
    {
        return [
            'heic file gets jpeg extension' => [
                'mimeType' => 'image/heic',
                'originalFilename' => 'photo.heic',
                'expectedKey' => '200/client/photo.heic.jpeg'
            ],
            'heif file gets jpeg extension' => [
                'mimeType' => 'image/heif',
                'originalFilename' => 'photo.heif',
                'expectedKey' => '200/client/photo.heif.jpeg'
            ]
        ];
    }

    public function testUploadMultipleThrowsNotFoundExceptionWhenEventNotFound(): void
    {
        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['order_id' => 123])
            ->willReturn(null);

        $this->mediaRepository->expects($this->never())->method('save');

        $this->bucketProvider->expects($this->never())->method('putObject');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Event not found for order: 123');

        $this->mediaService->uploadMultiple(123, []);
    }

    public function testUploadMultipleSucceedsWithSingleVideoFile(): void
    {
        $event = new Profile();
        $orderId = 123;
        $tempFile = $this->createTempFile('video content');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getClientOriginalName')->willReturn('video.mp4');
        $uploadedFile->method('getMimeType')->willReturn('video/mp4');
        $uploadedFile->method('getPathname')->willReturn($tempFile);

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['order_id' => $orderId])
            ->willReturn($event);

        $this->mediaRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn(Media $media) => (
                    $media->getEvent() === $event
                    && $media->getFilePath() === '123/client/video.mp4'
                    && $media->getUploaderHash() === 'client'
                    && $media->getFileType() === 'video/mp4'
                    && $media->getOriginalFilename() === 'video.mp4'
                    && $media->getThumbnailPath() === null
                )
            ));

        $this->bucketProvider
            ->expects($this->once())
            ->method('putObject')
            ->with('123/client/video.mp4', 'video content', 'video/mp4');

        $this->mediaService->uploadMultiple($orderId, [$uploadedFile]);

        unlink($tempFile);
    }

    public function testUploadMultipleSucceedsWithCustomFolder(): void
    {
        $event = new Profile();
        $orderId = 456;
        $customHash = 'guest-hash-123';
        $tempFile = $this->createTempFile('file content');

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getClientOriginalName')->willReturn('document.pdf');
        $uploadedFile->method('getMimeType')->willReturn('application/pdf');
        $uploadedFile->method('getPathname')->willReturn($tempFile);

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['order_id' => $orderId])
            ->willReturn($event);

        $this->mediaRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn(Media $media) => (
                    $media->getFilePath() === '456/guest-hash-123/document.pdf'
                    && $media->getUploaderHash() === $customHash
                )
            ));

        $this->bucketProvider
            ->expects($this->once())
            ->method('putObject')
            ->with('456/guest-hash-123/document.pdf', 'file content', 'application/pdf');

        $this->mediaService->uploadMultiple($orderId, [$uploadedFile], $customHash);

        unlink($tempFile);
    }

    public function testUploadMultipleSucceedsWithMultipleFiles(): void
    {
        $event = new Profile();
        $orderId = 789;
        $tempFile1 = $this->createTempFile('content1');
        $tempFile2 = $this->createTempFile('content2');

        $uploadedFile1 = $this->createMock(UploadedFile::class);
        $uploadedFile1->method('isValid')->willReturn(true);
        $uploadedFile1->method('getClientOriginalName')->willReturn('file1.txt');
        $uploadedFile1->method('getMimeType')->willReturn('text/plain');
        $uploadedFile1->method('getPathname')->willReturn($tempFile1);

        $uploadedFile2 = $this->createMock(UploadedFile::class);
        $uploadedFile2->method('isValid')->willReturn(true);
        $uploadedFile2->method('getClientOriginalName')->willReturn('file2.txt');
        $uploadedFile2->method('getMimeType')->willReturn('text/plain');
        $uploadedFile2->method('getPathname')->willReturn($tempFile2);

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($event);

        $this->mediaRepository->expects($this->exactly(2))->method('save');

        $this->bucketProvider->expects($this->exactly(2))->method('putObject');

        $this->mediaService->uploadMultiple($orderId, [$uploadedFile1, $uploadedFile2]);

        unlink($tempFile1);
        unlink($tempFile2);
    }

    public function testUploadMultipleWithImageCreatesMainAndThumbnail(): void
    {
        $event = new Profile();
        $orderId = 100;
        $tempFile = $this->createTempImageFile(400, 300);

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getClientOriginalName')->willReturn('photo.jpg');
        $uploadedFile->method('getMimeType')->willReturn('image/jpeg');
        $uploadedFile->method('getPathname')->willReturn($tempFile);

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($event);

        $this->mediaRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn(Media $media) => (
                    $media->getFilePath() === '100/client/photo.jpg'
                    && $media->getThumbnailPath() === '100/client/photo_thumb.jpg'
                )
            ));

        $this->bucketProvider
            ->expects($this->exactly(2))
            ->method('putObject')
            ->willReturnCallback(function (string $key, string $content, string $mimeType) {
                $this->assertContains($key, ['100/client/photo.jpg', '100/client/photo_thumb.jpg']);
                $this->assertSame('image/jpeg', $mimeType);
            });

        $this->mediaService->uploadMultiple($orderId, [$uploadedFile]);

        unlink($tempFile);
    }

    /**
     * @dataProvider appleExtensionsDataProvider
     * @requires extension imagick
     */
    public function testUploadMultipleWithAppleExtensionAppendsJpegToKey(
        string $mimeType,
        string $originalFilename,
        string $expectedKey
    ): void {
        $event = new Profile();
        $orderId = 200;
        $tempFile = $this->createTempImageFile(400, 300);

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getClientOriginalName')->willReturn($originalFilename);
        $uploadedFile->method('getMimeType')->willReturn($mimeType);
        $uploadedFile->method('getPathname')->willReturn($tempFile);

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($event);

        $this->mediaRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn(Media $media) => $media->getFilePath() === $expectedKey && $media->getFileType() === $mimeType
            ));

        $this->bucketProvider->expects($this->atLeastOnce())->method('putObject');

        $this->mediaService->uploadMultiple($orderId, [$uploadedFile]);

        unlink($tempFile);
    }

    public function testAppleExtensionsConstantContainsExpectedMimeTypes(): void
    {
        $this->assertContains('image/heic', MediaService::APPLE_EXTENSIONS);
        $this->assertContains('image/heif', MediaService::APPLE_EXTENSIONS);
        $this->assertCount(2, MediaService::APPLE_EXTENSIONS);
    }

    /**
     * @dataProvider buildUrlDataProvider
     */
    public function testBuildUrlReturnsCorrectS3Url(string $prefix, string $expectedUrl): void
    {
        $result = $this->mediaService->buildUrl($prefix);

        $this->assertSame($expectedUrl, $result);
    }

    protected function setUp(): void
    {
        $this->bucketProvider = $this->createMock(BucketProviderInterface::class);
        $this->eventRepository = $this->createMock(ProfileRepository::class);
        $this->mediaRepository = $this->createMock(MediaRepository::class);

        $this->mediaService = new MediaService(
            $this->bucketProvider,
            $this->eventRepository,
            $this->mediaRepository,
            'test-bucket',
            'eu-west-1'
        );
    }

    private function createTempFile(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, $content);

        return $tempFile;
    }

    private function createTempImageFile(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.jpg';
        imagejpeg($image, $tempFile, 90);
        imagedestroy($image);

        return $tempFile;
    }
}
