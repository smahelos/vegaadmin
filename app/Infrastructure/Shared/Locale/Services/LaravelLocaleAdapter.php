<?php

namespace App\Infrastructure\Shared\Locale\Services;

use App\Domain\User\Contracts\Locale;

class LaravelLocaleAdapter implements Locale
{
    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale(): string
    {
        return config('app.locale', 'cs');
    }
    
    /**
     * Get available locales from config.
     *
     * @return array<string>
     */
    public function getAvailableLocales(): array
    {
        return config('app.available_locales', ['cs', 'en', 'de', 'sk']);
    }

    /**
     * Get fallback locale from config.
     *
     * @return string
     */
    public function getFallbackLocale(): string
    {
        return config('app.fallback_locale', 'cs');
    }

    /**
     * Get country locale map from config. 
     *
     * @return array<string>
     */
    public function getCountryLocaleMap(): array
    {
        return config('locale.country_locale_map', []);
    }

    /**
     * Set application locale.
     *
     * @param string $locale Locale to set
     * @return void
     */
    public function setLocale(string $locale): void
    {
        app()->setLocale($locale);
    }

    /**
     * Map country code to preferred locale using configuration.
     * Falls back to app.fallback_locale when not found or empty.
     */
    public function localeFromCountry(?string $country): string
    {
        $fallback = $this->getFallbackLocale();
        if (!$country) {
            return $fallback;
        }
        $map = $this->getCountryLocaleMap();
        $code = strtoupper($country);
        return $map[$code] ?? $fallback;
    }

    /**
     * Summary of determineLocale
     * @param mixed $requestLocale
     * @param mixed $dataLocale
     * @return void
     */
    public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string
    {
        $availableLocales = $this->getAvailableLocales();
        $fallbackLocale = $this->getFallbackLocale();

        // If request locale is available, use it
        if ($requestLocale && in_array($requestLocale, $availableLocales)) {
            return $requestLocale;
        }

        // If data locale is available, use it
        if ($dataLocale && in_array($dataLocale, $availableLocales)) {
            return $dataLocale;
        }

        // Fallback to default locale
        return $fallbackLocale;
    }
}
