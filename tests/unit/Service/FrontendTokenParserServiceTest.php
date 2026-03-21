<?php

declare(strict_types = 1);

namespace App\Tests\unit\Service;

use App\Domain\ValueObject\UserRole;
use App\Exception\Auth\ExpiredException;
use App\Exception\Auth\InvalidHmacException;
use App\Exception\Auth\InvalidStructureException;
use App\Service\FrontendTokenParserService;
use PHPUnit\Framework\TestCase;

class FrontendTokenParserServiceTest extends TestCase
{
    private const API_KEY = 'test-api-key';

    public function testDecodeAdminToken(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $email = 'admin@email.com';
        $expiration = time() + 86_400; // +1 day

        $data = $email . '|' . $expiration;
        $hmac = hash_hmac(FrontendTokenParserService::HASH_ALGO, $data, self::API_KEY);
        $token = $data . '|' . $hmac;

        [$userRole, $parsedEmail] = $tokenParser->validateTokenAndGetUserInfo($token);

        $this->assertEquals(UserRole::ROLE_ADMIN, $userRole);
        $this->assertEquals($email, $parsedEmail);
    }

    public function testDecodeSuperAdminToken(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $email = FrontendTokenParserService::SUPER_ADMIN_EMAIL;
        $expiration = time() + 86_400; // +1 day

        $data = $email . '|' . $expiration;
        $hmac = hash_hmac(FrontendTokenParserService::HASH_ALGO, $data, self::API_KEY);
        $token = $data . '|' . $hmac;

        [$userRole, $parsedEmail] = $tokenParser->validateTokenAndGetUserInfo($token);

        $this->assertEquals(UserRole::ROLE_SUPER_ADMIN, $userRole);
        $this->assertEquals($email, $parsedEmail);
    }

    public function testDecodeGuestToken(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $token = 'guest_token';

        [$userRole, $parsedEmail] = $tokenParser->validateTokenAndGetUserInfo($token);

        $this->assertEquals(UserRole::ROLE_GUEST, $userRole);
        $this->assertEquals(null, $parsedEmail);
    }

    public function testDecodeThrowsInvalidHmacException(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $username = 'admin';
        $expiration = time() + 86_400;
        $token = $username . '|' . $expiration . '|' . 'invalid-hmac';

        $this->expectException(InvalidHmacException::class);
        $this->expectExceptionMessage('Invalid token');

        $tokenParser->validateTokenAndGetUserInfo($token);
    }

    public function testDecodeThrowsExpiredException(): void
    {
        $tokenParser = new FrontendTokenParserService(self::API_KEY);

        $username = 'admin';
        $expiration = time() - 86_400; // -1 day (expired)
        $token = $username . '|' . $expiration . '|' . 'any-hmac';

        $this->expectException(ExpiredException::class);
        $this->expectExceptionMessage('Expired token');

        $tokenParser->validateTokenAndGetUserInfo($token);
    }
}
