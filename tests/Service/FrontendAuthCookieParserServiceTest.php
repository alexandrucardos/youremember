<?php

namespace App\Tests\Service;

use App\Exception\Auth\ExpiredException;
use App\Exception\Auth\InvalidHmacException;
use App\Exception\Auth\InvalidStructureException;
use App\Service\FrontendTokenParserService;
use PHPUnit\Framework\TestCase;
use function Symfony\Component\Clock\now;

class FrontendAuthCookieParserServiceTest extends TestCase
{
    public function testFrontendAuthCookie(): void
    {
        $frontendApiKey = $_ENV['FE_AUTH_TOKEN'];

        $frontendAuthCookieParserService = new FrontendTokenParserService(
            frontendApiKey: $frontendApiKey,
        );

        $username = 'admin';
        $expiration = now('+ 1 day')->getTimestamp();

        $data = $username . '|' . $expiration;

        $expectedHmac = hash_hmac(
            algo: FrontendTokenParserService::HASH_ALGO,
            data: $data,
            key: $frontendApiKey,
        );

        $response = $frontendAuthCookieParserService->decodeEmail($data . '|' . $expectedHmac);

        $this->assertNotNull($response);
    }

    public function testInvalidStructureException(): void
    {
        $frontendApiKey = $_ENV['FE_AUTH_TOKEN'];
        $frontendAuthCookieParserService = new FrontendTokenParserService(
            frontendApiKey: $frontendApiKey,
        );

        $this->expectException(InvalidStructureException::class);

        $frontendAuthCookieParserService->decodeEmail('invalid|str');
    }

    public function testInvalidHmacException(): void
    {
        $frontendApiKey = $_ENV['FE_AUTH_TOKEN'];
        $frontendAuthCookieParserService = new FrontendTokenParserService(
            frontendApiKey: $frontendApiKey,
        );

        $username = 'admin';
        $expiration = now('+ 1 day')->getTimestamp();

        $cookie = $username . '|' . $expiration . '|' . 'asdf';

        $this->expectException(InvalidHmacException::class);

        $frontendAuthCookieParserService->decodeEmail($cookie);
    }

    public function testExpiredHmacException(): void
    {
        $frontendApiKey = $_ENV['FE_AUTH_TOKEN'];
        $frontendAuthCookieParserService = new FrontendTokenParserService(
            frontendApiKey: $frontendApiKey,
        );

        $username = 'admin';
        $expiration = now('- 1 day')->getTimestamp();
        $cookie = $username . '|' . $expiration . '|' . 'asdf';
        $this->expectException(ExpiredException::class);
        $frontendAuthCookieParserService->decodeEmail($cookie);
    }

}
