<?php

namespace App\Infrastructure\Shared\Str\Services;

use App\Domain\Shared\Str\Contracts\Str;

/**
 * Adapter to bridge Domain's Str with Laravel's Str facade.
 */
class LaravelStrAdapter implements Str
{
    /**
     * Generate a URL-friendly slug from a string.
     */
    public function slug(string $title, string $separator = '-', string $language = 'en', array $dictionary = ['@' => 'at']): string
    {
        return \Illuminate\Support\Str::slug($title, $separator, $language, $dictionary);
    }
}
