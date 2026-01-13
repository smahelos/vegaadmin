<?php

namespace App\Domain\Shared\Translation\Contracts;

/**
 * Framework-agnostic translation interface for Domain layer.
 * Allows Domain to perform key-based translations without depending on Laravel.
 */
interface TranslatorInterface
{
    /**
     * Check if a translation key exists for the given locale.
     */
    public function has(string $key, ?string $locale = null): bool;

    /**
     * Translate a key using provided replacements and locale.
     */
    public function trans(string $key, array $replace = [], ?string $locale = null): string;
}
