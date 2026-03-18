<?php

declare(strict_types = 1);

namespace App\Tests\unit\ValueObject;

use App\Exception\Event\InvalidOrderIdException;
use App\ValueObject\OrderIdValueObject;
use PHPUnit\Framework\TestCase;

final class OrderIdValueObjectTest extends TestCase
{
    public static function validIdProvider(): iterable
    {
        yield 'integer' => [123, 123];
        yield 'string numeric' => ['456', 456];
        yield 'large integer' => [999_999, 999_999];
    }

    public static function invalidIdProvider(): iterable
    {
        yield 'non-numeric string' => ['invalid'];
        yield 'zero' => [0];
        yield 'negative integer' => [-5];
        yield 'float' => [12.5];
        yield 'null' => [null];
        yield 'empty string' => [''];
    }

    /**
     * @dataProvider validIdProvider
     */
    public function testCreatesWithValidId(mixed $input, int $expected): void
    {
        $orderId = new OrderIdValueObject($input);

        self::assertSame($expected, $orderId->value);
    }

    /**
     * @dataProvider invalidIdProvider
     */
    public function testThrowsExceptionForInvalidId(mixed $input): void
    {
        $this->expectException(InvalidOrderIdException::class);

        new OrderIdValueObject($input);
    }
}
