<?php

namespace App\Domain\Shared\Str\Contracts;

/**
 * Str contract.
 * Contract for Str implementation
 */
interface Str
{
    /**
     * Generate a URL-friendly slug from a given string.
     */
    public function slug(
        string $title,
        string $separator = '-',
        string $language = 'en',
        array $dictionary = ['@' => 'at'],
    ): string;
}
