<?php

namespace App\Infrastructure\Shared\Hash;

use App\Domain\Shared\Hash\Contracts\HashInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Adapter to bridge Domain's HashInterface with Laravel's hashing.
 */
class LaravelHash implements HashInterface
{
    /**
     * Hashes a string using Laravel's Hash facade.
     */
    public function hash(string $value): string
    {
        // Delegate to Laravel's Hash facade
        return Hash::make($value);
    }

    public function needsRehash(string $hashedValue): bool
    {
        return Hash::needsRehash($hashedValue);
    }

    public function verify(string $value, string $hashedValue): bool
    {
        return Hash::check($value, $hashedValue);
    }
}
