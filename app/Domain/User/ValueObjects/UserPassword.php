<?php

namespace App\Domain\User\ValueObjects;

use App\Domain\Shared\Hash\Contracts\HashInterface;
use InvalidArgumentException;

/**
 * User Password Value Object
 *
 * Handles password validation, hashing and security requirements
 */
class UserPassword
{
    private string $hashedPassword;

    /**
     * Default hasher instance for factories when none provided.
     * Set by Infrastructure/Application during bootstrapping.
     */
    private static ?HashInterface $defaultHasher = null;

    /**
     * Private constructor to enforce factory methods
     */
    private function __construct(string $hashedPassword)
    {
        $this->hashedPassword = $hashedPassword;
    }

    /**
     * Configure default hasher to be used by factory methods.
     */
    public static function setHasher(HashInterface $hasher): void
    {
        self::$defaultHasher = $hasher;
    }

    /**
     * Create from plain text password
     */
    public static function fromPlainText(string $plainPassword, ?HashInterface $hasher = null): self
    {
        self::validatePlainPassword($plainPassword);
        $hasher = $hasher ?? self::$defaultHasher;
        if (! $hasher) {
            throw new InvalidArgumentException('Hash service is not configured');
        }
        $hashedPassword = $hasher->hash($plainPassword);

        return new self($hashedPassword);
    }

    /**
     * Validate plain text password requirements
     */
    private static function validatePlainPassword(string $password): void
    {
        if (empty($password)) {
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

        if (in_array(strtolower($password), $weakPasswords)) {
            throw new InvalidArgumentException('Password is too common and weak');
        }
    }

    /**
     * Convert to string (returns hashed value)
     */
    public function __toString(): string
    {
        return $this->hashedPassword;
    }
}
