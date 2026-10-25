<?php

namespace App\Infrastructure\Shared\Locale\Services;

use App\Domain\User\Contracts\Locale;

class LaravelLocaleAdapter implements Locale
{
    /**
     * Get locale
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
        return config('app.available_locales', [
            'cs',
            'en',
            'de',
            'sk',
        ]);
    }

    /**
     * Get fallback locale from config.
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
     * Summary of determineLocale
     *
     * @param  mixed  $requestLocale
     * @param  mixed  $dataLocale
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
