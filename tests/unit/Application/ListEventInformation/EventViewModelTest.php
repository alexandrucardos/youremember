<?php

declare(strict_types = 1);

namespace App\Tests\unit\Application\ListEventInformation;

use App\Application\ListProfileInformation\ProfileViewModel;
use PHPUnit\Framework\TestCase;

class EventViewModelTest extends TestCase
{
    private const PROFILE_ID = 1;
    private const EVENT_NAME = 'Summer Wedding';
    private const EVENT_FONT = 'elegant';

    private function buildViewModel(): ProfileViewModel
    {
        return new ProfileViewModel(
            id: self::PROFILE_ID,
            name: self::EVENT_NAME,
            nameFont: self::EVENT_FONT,
            bornAt: null,
            departedAt: null,
            obituary: null
        );
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = $this->buildViewModel()->toArray();

        $this->assertSame(self::PROFILE_ID, $result['id']);
        $this->assertSame(self::EVENT_NAME, $result['name']);
        $this->assertSame(self::EVENT_FONT, $result['name_font']);
    }

    public function testToArrayKeysAreCorrect(): void
    {
        $result = $this->buildViewModel()->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('name_font', $result);
        $this->assertArrayHasKey('born_at', $result);
        $this->assertArrayHasKey('deceased_at', $result);
        $this->assertArrayHasKey('obituary', $result);
    }
}
