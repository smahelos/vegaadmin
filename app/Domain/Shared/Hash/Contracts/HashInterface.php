<?php

namespace App\Domain\Shared\Hash\Contracts;

interface HashInterface
{
    /**
     * Hashes a string using Laravel's Hash facade.
     */
    public function hash(string $value): string;
}
