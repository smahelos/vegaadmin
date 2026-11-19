<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

use App\Domain\Shared\Hash\Contracts\HashInterface;
use App\Domain\User\Exceptions\CommonPasswordException;
use InvalidArgumentException;

/**
 * A password that passed the password rules, held only as its hash.
 */
final readonly class UserPassword
{
    private function __construct(
        public string $hash,
    ) {}

    public static function fromPlainText(string $plainPassword, HashInterface $hasher): self
    {
        self::validatePlainPassword($plainPassword);

        return new self(
            $hasher->hash($plainPassword),
        );
    }

    private static function validatePlainPassword(string $password): void
    {
        if ($password === '') {
            throw new InvalidArgumentException('Password cannot be empty');
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password',
            'password123',
            '12345678',
            'qwerty123',
            'admin123',
            'user123',
            'test123',
            '123456789',
        ];

        if (
            in_array(
                strtolower($password),
                $weakPasswords,
            )
        ) {
            throw new CommonPasswordException;
        }
    }
}
