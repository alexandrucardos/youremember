<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\ListEventInformation;

use App\Application\ListEventInformation\EventViewModel;
use App\Application\ListEventInformation\MediaViewModel;
use App\ValueObject\EventNameFontValueObject;
use App\ValueObject\EventNameValueObject;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;

class EventViewModelTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';
    private const EVENT_NAME = 'Summer Wedding';
    private const EVENT_FONT = 'elegant';
    private const BACKGROUND_URL = 'https://bucket.s3.region.amazonaws.com/123/client/background';

    public static function toArrayDataProvider(): array
    {
        return [
            'with pictures' => [
                'picturesUrls' => ['https://example.com/pic1.jpg', 'https://example.com/pic2.jpg']
            ],
            'no pictures' => [
                'picturesUrls' => []
            ]
        ];
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArrayReturnsCorrectStructure(array $picturesUrls): void
    {
        $viewModel = $this->buildViewModel($picturesUrls);

        $result = $viewModel->toArray();

        $this->assertSame(self::UUID, $result['token']);
        $this->assertSame(self::EVENT_NAME, $result['name']);
        $this->assertSame(self::EVENT_FONT, $result['font']);
        $this->assertSame(self::BACKGROUND_URL, $result['media']['backgroundPictureUrl']);
        $this->assertSame($picturesUrls, $result['media']['pictures']);
    }

    public function testToArrayKeysAreCorrect(): void
    {
        $result = $this->buildViewModel([])->toArray();

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('font', $result);
        $this->assertArrayHasKey('media', $result);
        $this->assertArrayHasKey('backgroundPictureUrl', $result['media']);
        $this->assertArrayHasKey('pictures', $result['media']);
    }

    private function buildViewModel(array $picturesUrls): EventViewModel
    {
        return new EventViewModel(
            eventUuid: new UuidValueObject(self::UUID),
            eventName: new EventNameValueObject(self::EVENT_NAME),
            eventNameFont: new EventNameFontValueObject(self::EVENT_FONT),
            media: new MediaViewModel(backgroundPictureUrl: self::BACKGROUND_URL, picturesUrls: $picturesUrls)
        );
    }
}
