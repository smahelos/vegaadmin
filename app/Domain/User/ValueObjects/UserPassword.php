<?php

namespace App\Domain\User\ValueObjects;

use InvalidArgumentException;
use App\Domain\Shared\Hash\Contracts\HashInterface;

/**
 * User Password Value Object
 * 
 * Handles password validation, hashing and security requirements
 */
class UserPassword
{
    private string $hashedPassword;

    private HashInterface $hashService;

    /**
     * Default hasher instance for factories when none provided.
     * Set by Infrastructure/Application during bootstrapping.
     */
    private static ?HashInterface $defaultHasher = null;

    /**
     * Private constructor to enforce factory methods
     */
    private function __construct(
        string $hashedPassword,
        HashInterface $hashService
    ) {
        $this->hashedPassword = $hashedPassword;
        $this->hashService = $hashService;
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
        if (!$hasher) {
            throw new InvalidArgumentException('Hash service is not configured');
        }
        $hashedPassword = $hasher->hash($plainPassword);
        return new self($hashedPassword, $hasher);
    }

    /**
     * Create from already hashed password
     */
    public static function fromHash(string $hashedPassword, ?HashInterface $hasher = null): self
    {
        if (empty($hashedPassword)) {
            throw new InvalidArgumentException('Hashed password cannot be empty');
        }
        $hasher = $hasher ?? self::$defaultHasher;
        if (!$hasher) {
            throw new InvalidArgumentException('Hash service is not configured');
        }
        return new self($hashedPassword, $hasher);
    }

    /**
     * Validate plain text password requirements
     */
    private static function validatePlainPassword(string $password): void
    {
        if (empty($password)) {
            throw new InvalidArgumentException('Password cannot be empty');
        }

        $length = strlen($password);
        // if ($length < 8) {
        //     throw new InvalidArgumentException("Password too short, minimum 8 characters, got: {$length}");
        // }

        // if ($length > 255) {
        //     throw new InvalidArgumentException("Password too long, maximum 255 characters, got: {$length}");
        // }

        // // Check for at least one lowercase letter
        // if (!preg_match('/[a-z]/', $password)) {
        //     throw new InvalidArgumentException('Password must contain at least one lowercase letter');
        // }

        // // Check for at least one uppercase letter
        // if (!preg_match('/[A-Z]/', $password)) {
        //     throw new InvalidArgumentException('Password must contain at least one uppercase letter');
        // }

        // // Check for at least one digit
        // if (!preg_match('/[0-9]/', $password)) {
        //     throw new InvalidArgumentException('Password must contain at least one digit');
        // }

        // Check for common weak passwords
        $weakPasswords = [
            'password', 'password123', '12345678', 'qwerty123',
            'admin123', 'user123', 'test123', '123456789'
        ];

        if (in_array(strtolower($password), $weakPasswords)) {
            throw new InvalidArgumentException('Password is too common and weak');
        }
    }

    /**
     * Get hashed password
     */
    public function getHash(): string
    {
        return $this->hashedPassword;
    }

    /**
     * Verify if plain text password matches this hash
     */
    public function verify(string $plainPassword): bool
    {
        return $this->hashService->verify($plainPassword, $this->hashedPassword);
    }

    /**
     * Check if password needs rehashing (for security updates)
     */
    public function needsRehash(): bool
    {
        return $this->hashService->needsRehash($this->hashedPassword);
    }

    /**
     * Create new password with rehashed value
     */
    public function rehash(): self
    {
        if (!$this->needsRehash()) {
            return $this;
        }

        // We can't rehash without the plain password
        throw new InvalidArgumentException('Cannot rehash password without original plain text');
    }

    /**
     * Get password strength score (0-100)
     */
    public static function getStrengthScore(string $plainPassword): int
    {
        $score = 0;
        $length = strlen($plainPassword);

        // Length scoring
        if ($length >= 8) $score += 25;
        if ($length >= 12) $score += 15;
        if ($length >= 16) $score += 10;

        // Character variety scoring
        if (preg_match('/[a-z]/', $plainPassword)) $score += 10;
        if (preg_match('/[A-Z]/', $plainPassword)) $score += 10;
        if (preg_match('/[0-9]/', $plainPassword)) $score += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $plainPassword)) $score += 15;

        // Pattern penalties
        if (preg_match('/(.)\1{2,}/', $plainPassword)) $score -= 10; // Repeated characters
        if (preg_match('/123|abc|qwe/i', $plainPassword)) $score -= 10; // Sequential characters

        return max(0, min(100, $score));
    }

    /**
     * Get password strength description
     */
    public static function getStrengthDescription(string $plainPassword): string
    {
        $score = self::getStrengthScore($plainPassword);

        if ($score < 30) return 'Weak';
        if ($score < 60) return 'Fair';
        if ($score < 80) return 'Good';
        return 'Strong';
    }

    /**
     * Check if password meets minimum security requirements
     */
    public static function meetsSecurityRequirements(string $plainPassword): bool
    {
        try {
            self::validatePlainPassword($plainPassword);
            return self::getStrengthScore($plainPassword) >= 50;
        } catch (InvalidArgumentException $e) {
            return false;
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
