<?php

namespace App\Tests\unit\Service\Media;

use App\Entity\Event;
use App\Entity\Media;
use App\Exception\Media\NotFoundException;
use App\Repository\EventRepository;
use App\Repository\MediaRepository;
use App\Service\Bucket\BucketProviderInterface;
use App\Service\Media\MediaService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaServiceTest extends TestCase
{
    private MediaService $mediaService;
    private MediaRepository $mediaRepository;
    private EventRepository $eventRepository;
    private BucketProviderInterface $bucketProvider;

    public static function buildUrlDataProvider(): array
    {
        return [
            'simple prefix' => [
                'prefix' => '123/client/photo.jpg',
                'expectedUrl' => 'https://test-bucket.s3.eu-west-1.amazonaws.com/123/client/photo.jpg',
            ],
            'prefix with spaces' => [
                'prefix' => '123/client/photo with spaces.jpg',
                'expectedUrl' => 'https://test-bucket.s3.eu-west-1.amazonaws.com/123/client/photo with spaces.jpg',
            ],
        ];
    }

    public function testUploadMultipleThrowsNotFoundExceptionWhenEventNotFound(): void
    {
        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['order_id' => 123])
            ->willReturn(null);

        $this->mediaRepository
            ->expects($this->never())
            ->method('save');

        $this->bucketProvider
            ->expects($this->never())
            ->method('putObject');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Event not found for order: 123');

        $this->mediaService->uploadMultiple(123, []);
    }

    public function testUploadMultipleSucceedsWithSingleVideoFile(): void
    {
        $event = new Event();
        $orderId = 123;
        $tempFile = $this->createTempFile('video content');

        $uploadedFile = $this->createMock(UploadedFile::class);
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
            ->with($this->callback(function (Media $media) use ($event) {
                return $media->getEvent() === $event
                    && $media->getFilePath() === '123/client/video.mp4'
                    && $media->getUploaderHash() === 'client'
                    && $media->getFileType() === 'video/mp4'
                    && $media->getOriginalFilename() === 'video.mp4'
                    && $media->getThumbnailPath() === null;
            }));

        $this->bucketProvider
            ->expects($this->once())
            ->method('putObject')
            ->with('123/client/video.mp4', 'video content', 'video/mp4');

        $this->mediaService->uploadMultiple($orderId, [$uploadedFile]);

        unlink($tempFile);
    }

    private function createTempFile(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, $content);

        return $tempFile;
    }

    public function testUploadMultipleSucceedsWithCustomFolder(): void
    {
        $event = new Event();
        $orderId = 456;
        $customHash = 'guest-hash-123';
        $tempFile = $this->createTempFile('file content');

        $uploadedFile = $this->createMock(UploadedFile::class);
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
            ->with($this->callback(function (Media $media) use ($customHash) {
                return $media->getFilePath() === '456/guest-hash-123/document.pdf'
                    && $media->getUploaderHash() === $customHash;
            }));

        $this->bucketProvider
            ->expects($this->once())
            ->method('putObject')
            ->with('456/guest-hash-123/document.pdf', 'file content', 'application/pdf');

        $this->mediaService->uploadMultiple($orderId, [$uploadedFile], $customHash);

        unlink($tempFile);
    }

    public function testUploadMultipleSucceedsWithMultipleFiles(): void
    {
        $event = new Event();
        $orderId = 789;
        $tempFile1 = $this->createTempFile('content1');
        $tempFile2 = $this->createTempFile('content2');

        $uploadedFile1 = $this->createMock(UploadedFile::class);
        $uploadedFile1->method('getClientOriginalName')->willReturn('file1.txt');
        $uploadedFile1->method('getMimeType')->willReturn('text/plain');
        $uploadedFile1->method('getPathname')->willReturn($tempFile1);

        $uploadedFile2 = $this->createMock(UploadedFile::class);
        $uploadedFile2->method('getClientOriginalName')->willReturn('file2.txt');
        $uploadedFile2->method('getMimeType')->willReturn('text/plain');
        $uploadedFile2->method('getPathname')->willReturn($tempFile2);

        $this->eventRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($event);

        $this->mediaRepository
            ->expects($this->exactly(2))
            ->method('save');

        $this->bucketProvider
            ->expects($this->exactly(2))
            ->method('putObject');

        $this->mediaService->uploadMultiple($orderId, [$uploadedFile1, $uploadedFile2]);

        unlink($tempFile1);
        unlink($tempFile2);
    }

    public function testUploadMultipleWithImageCreatesMainAndThumbnail(): void
    {
        $event = new Event();
        $orderId = 100;
        $tempFile = $this->createTempImageFile(400, 300);

        $uploadedFile = $this->createMock(UploadedFile::class);
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
            ->with($this->callback(function (Media $media) {
                return $media->getFilePath() === '100/client/photo.jpg'
                    && $media->getThumbnailPath() === '100/client/photo_thumb.jpg';
            }));

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
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->mediaRepository = $this->createMock(MediaRepository::class);

        $this->mediaService = new MediaService(
            $this->bucketProvider,
            $this->eventRepository,
            $this->mediaRepository,
            'test-bucket',
            'eu-west-1'
        );
    }
}