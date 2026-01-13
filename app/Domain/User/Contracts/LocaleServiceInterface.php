<?php

namespace App\Domain\User\Contracts;

interface LocaleServiceInterface
{
    /**
     * Get locale
     *
     * @return string
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
     *
     * @return string
     */
    public function getFallbackLocale(): string;

    /**
     * Get country locale map from config. 
     *
     * @return array<string>
     */
    public function getCountryLocaleMap(): array;
    
    /**
     * Set application locale and update session.
     *
     * @param string $locale Locale to set
     * @return void
     */
    public function setLocale(string $locale): void;


    /**
     * Map country code to preferred locale using configuration.
     * Falls back to app.fallback_locale when not found or empty.
     */
    public function localeFromCountry(?string $country): string;
}
