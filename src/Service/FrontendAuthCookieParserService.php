<?php

namespace App\Service;

use App\Exception\Auth\ExpiredException;
use App\Exception\Auth\InvalidHmacException;
use App\Exception\Auth\InvalidStructureException;

class FrontendAuthCookieParserService
{
    public const HASH_ALGO = 'sha256';

    public function __construct(
        private readonly string $frontendApiKey
    )
    {
    }

    public function decode(string $cookieValue): ?array
    {
        $parts = explode('|', $cookieValue);

        if (count($parts) !== 3) {
            throw new InvalidStructureException('Invalid cookie value');
        }

        [$username, $expiration, $hmac] = $parts;

        if ((int)$expiration < time()) {
            throw new ExpiredException('Expired token');
        }

        $data = $username . '|' . $expiration;


        $expectedHmac = hash_hmac(
            algo: self::HASH_ALGO,
            data: $data,
            key: $this->frontendApiKey,
        );

        if (!hash_equals($expectedHmac, $hmac)) {
            throw new InvalidHmacException('Invalid token');
        }

        return [
            'username' => $username,
            'expiration' => (int)$expiration,
        ];
    }
}