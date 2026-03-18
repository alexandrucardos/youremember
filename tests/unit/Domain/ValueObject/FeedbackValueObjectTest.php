<?php

declare(strict_types = 1);

namespace App\Tests\unit\Domain\ValueObject;

use App\Domain\ValueObject\FeedbackValueObject;
use PHPUnit\Framework\TestCase;

class FeedbackValueObjectTest extends TestCase
{
    public static function validValuesDataProvider(): array
    {
        return [
            'short feedback' => ['value' => 'ok'],
            'normal feedback' => ['value' => 'Great event!'],
            'long feedback' => ['value' => 'This was an absolutely wonderful event, highly recommend!']
        ];
    }

    public static function invalidValuesDataProvider(): array
    {
        return [
            'empty string' => ['value' => ''],
            'single char' => ['value' => 'a'],
            'null' => ['value' => null]
        ];
    }

    /**
     * @dataProvider validValuesDataProvider
     */
    public function testConstructorStoresValidValue(mixed $value): void
    {
        $feedbackValueObject = new FeedbackValueObject($value);

        $this->assertSame($value, $feedbackValueObject->value);
    }

    /**
     * @dataProvider invalidValuesDataProvider
     */
    public function testConstructorThrowsForInvalidValue(mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FeedbackValueObject($value);
    }
}
