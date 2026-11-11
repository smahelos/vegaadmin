<?php

namespace App\Domain\User\Contracts;

interface Locale
{
    /**
     * Get locale
     */
    public function getLocale(): string;

    /**
     * Get available locales from config.
     *
     * @return array<string>
     */
    public function getAvailableLocales(): array;

    /**
     * Get fallback locale from config.
     */
    public function getFallbackLocale(): string;

    /**
     * Get country locale map from config.
     *
     * @return array<string>
     */
    public function getCountryLocaleMap(): array;

    public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string;
}
