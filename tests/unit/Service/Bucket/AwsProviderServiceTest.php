<?php

declare(strict_types = 1);

namespace App\Tests\unit\Service\Bucket;

use App\Service\Bucket\AwsProviderService;
use AsyncAws\Core\Stream\ResultStream;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\S3Client;
use PHPUnit\Framework\TestCase;

class AwsProviderServiceTest extends TestCase
{
    private string $tempDir;

    public static function downloadFilesDataProvider(): array
    {
        return [
            'single file url' => [
                'fileUrls' => ['146/client/image.jpg']
            ],
            'multiple urls - all processed' => [
                'fileUrls' => [
                    '146/client/image.jpg',
                    '146/client/image2.jpg',
                    '146/client/image3.jpg'
                ]
            ]
        ];
    }

    /**
     * @dataProvider downloadFilesDataProvider
     */
    public function testDownloadsAllFiles(array $fileUrls): void
    {
        $resultStream = new class implements ResultStream, \Stringable {
            public function getChunks(): iterable
            {
                return [];
            }

            public function getContentAsString(): string
            {
                return 'file-content';
            }

            public function getContentAsResource()
            {
                return fopen('php://memory', 'r');
            }

            public function __toString(): string
            {
                return 'file-content';
            }
        };

        $getObjectOutput = $this->createMock(GetObjectOutput::class);
        $getObjectOutput->method('getBody')->willReturn($resultStream);

        $s3Client = $this->createMock(S3Client::class);
        $s3Client->expects($this->exactly(count($fileUrls)))->method('headObject');
        $s3Client->expects($this->exactly(count($fileUrls)))->method('getObject')->willReturn($getObjectOutput);

        $awsProviderService = new AwsProviderService($s3Client, 'test-bucket', $this->tempDir);
        $awsProviderService->downloadFilesForPaths($fileUrls);
    }

    public function testCreatesArchiveDirectoryForMainFolder(): void
    {
        $resultStream = new class implements ResultStream, \Stringable {
            public function getChunks(): iterable
            {
                return [];
            }

            public function getContentAsString(): string
            {
                return '';
            }

            public function getContentAsResource()
            {
                return fopen('php://memory', 'r');
            }

            public function __toString(): string
            {
                return '';
            }
        };

        $getObjectOutput = $this->createMock(GetObjectOutput::class);
        $getObjectOutput->method('getBody')->willReturn($resultStream);

        $s3Client = $this->createMock(S3Client::class);
        $s3Client->method('headObject');
        $s3Client->method('getObject')->willReturn($getObjectOutput);

        $awsProviderService = new AwsProviderService($s3Client, 'test-bucket', $this->tempDir);
        $awsProviderService->downloadFilesForPaths(['146/client/image.jpg']);

        $this->assertDirectoryExists($this->tempDir . '/archive/146');
    }

    public function testSkipsGetObjectWhenHeadObjectThrowsException(): void
    {
        $s3Client = $this->createMock(S3Client::class);
        $s3Client
            ->expects($this->once())
            ->method('headObject')
            ->willThrowException(new \Exception('S3 connection error'));
        $s3Client->expects($this->never())->method('getObject');

        $awsProviderService = new AwsProviderService($s3Client, 'test-bucket', $this->tempDir);
        $awsProviderService->downloadFilesForPaths(['146/client/image.jpg']);
    }

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/aws_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
