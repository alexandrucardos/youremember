<?php

namespace App\Tests\Service;

use App\Exception\Auth\ExpiredException;
use App\Exception\Auth\InvalidHmacException;
use App\Exception\Auth\InvalidStructureException;
use App\Service\FrontendTokenParserService;
use PHPUnit\Framework\TestCase;

class FrontendTokenParserServiceTest extends TestCase
{
    private const API_KEY = 'test-api-key';

    public function testDecodeValidToken(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $username = 'admin';
        $expiration = time() + 86400; // +1 day

        $data = $username . '|' . $expiration;
        $hmac = hash_hmac(FrontendTokenParserService::HASH_ALGO, $data, self::API_KEY);
        $token = $data . '|' . $hmac;

        $tokenParser->validateToken($token);

        // If we reach this point without exception, the token was valid
        $this->addToAssertionCount(1);
    }

    public function testDecodeThrowsInvalidStructureException(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $this->expectException(InvalidStructureException::class);
        $this->expectExceptionMessage('Invalid token value');

        $tokenParser->validateToken('invalid|str');
    }

    public function testDecodeThrowsInvalidHmacException(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $username = 'admin';
        $expiration = time() + 86400;
        $token = $username . '|' . $expiration . '|' . 'invalid-hmac';

        $this->expectException(InvalidHmacException::class);
        $this->expectExceptionMessage('Invalid token');

        $tokenParser->validateToken($token);
    }

    public function testDecodeThrowsExpiredException(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $username = 'admin';
        $expiration = time() - 86400; // -1 day (expired)
        $token = $username . '|' . $expiration . '|' . 'any-hmac';

        $this->expectException(ExpiredException::class);
        $this->expectExceptionMessage('Expired token');

        $tokenParser->validateToken($token);
    }
}
