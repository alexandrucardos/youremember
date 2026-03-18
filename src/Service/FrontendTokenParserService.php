<?php

declare(strict_types = 1);

namespace App\Service;

use App\Exception\Auth\ExpiredException;
use App\Exception\Auth\InvalidHmacException;
use App\Exception\Auth\InvalidStructureException;
use App\ValueObject\HashValueObject;
use App\ValueObject\UserRole;

class FrontendTokenParserService
{
    public const HASH_ALGO = 'sha256';
    public const SUPER_ADMIN_EMAIL = 'admin@eventsphotoshare.ro';

    public function __construct(
        private readonly string $frontendApiKey
    ) {
    }

    /**
     * @throws ExpiredException
     * @throws InvalidHmacException
     * @throws \InvalidArgumentException
     * @returns  array<UserRole, ?string>
     */
    public function validateTokenAndGetUserInfo(string $token): array
    {
        $parts = explode('|', $token);

        if (count($parts) !== 3) {
            new HashValueObject($token);

            return [UserRole::ROLE_GUEST, null];
        }

        [$email, $expiration, $hmac] = $parts;

        if ((int) $expiration < time()) {
            throw new ExpiredException('Expired token');
        }

        $data = $email . '|' . $expiration;

        $expectedHmac = hash_hmac(algo: self::HASH_ALGO, data: $data, key: $this->frontendApiKey);

        if (!hash_equals($expectedHmac, $hmac)) {
            throw new InvalidHmacException('Invalid token');
        }

        if ($email === self::SUPER_ADMIN_EMAIL) {
            return [UserRole::ROLE_SUPER_ADMIN, $email];
        }

        return [UserRole::ROLE_ADMIN, $email];
    }
}
