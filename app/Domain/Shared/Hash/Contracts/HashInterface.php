<?php

namespace App\Domain\Shared\Hash\Contracts;

interface HashInterface
{
    /**
     * Hashes a string using Laravel's Hash facade.
     */
    public function hash(string $value): string;

    public function needsRehash(string $hashedValue): bool;

    public function verify(string $value, string $hashedValue): bool;
}
