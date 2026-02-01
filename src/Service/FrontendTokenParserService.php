<?php

namespace App\Service;

use App\Exception\Auth\ExpiredException;
use App\Exception\Auth\InvalidHmacException;
use App\Exception\Auth\InvalidStructureException;
use App\ValueObject\EmailValueObject;

class FrontendTokenParserService
{
    public const HASH_ALGO = 'sha256';

    public function __construct(
        private readonly string $frontendApiKey
    )
    {
    }

    public function decodeEmail(string $token): EmailValueObject
    {
        $parts = explode('|', $token);

        if (count($parts) !== 3) {
            throw new InvalidStructureException('Invalid token value');
        }

        [$email, $expiration, $hmac] = $parts;

        if ((int)$expiration < time()) {
            throw new ExpiredException('Expired token');
        }

        $data = $email . '|' . $expiration;


        $expectedHmac = hash_hmac(
            algo: self::HASH_ALGO,
            data: $data,
            key: $this->frontendApiKey,
        );

        if (!hash_equals($expectedHmac, $hmac)) {
            throw new InvalidHmacException('Invalid token');
        }

        return new EmailValueObject($email);
    }
}