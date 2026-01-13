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
     *
     * @param string $title
     * @param string $separator
     * @param string $language
     * @param array $dictionary
     * @return string
     */
    public function slug(string $title, string $separator = '-', string $language = 'en', array $dictionary = ['@' => 'at']): string;
}
