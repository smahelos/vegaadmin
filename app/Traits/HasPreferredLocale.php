<?php

namespace App\Traits;

trait HasPreferredLocale
{
    /**
     * Determines preferred language based on country or returns default language
     *
     * @return string
     */
    public function getPreferredLocale(): string
    {
        // Mapping countries to languages
        $countryToLocaleMap = [
            'CZ' => 'cs',
            'CS' => 'cs',
            'SK' => 'sk',
            'DE' => 'de',
            'AT' => 'at', // Austria uses German
            'CH' => 'ch', // Switzerland (can be specified by region)
            // Add more countries as needed
        ];

        // If the model has a country field, use it directly
        if (isset($this->attributes['country'])) {
            $country = strtoupper($this->attributes['country']);
            return $countryToLocaleMap[$country] ?? config('app.fallback_locale', 'en');
        }

        // For cases where country is in another relationship (e.g. address)
        if (method_exists($this, 'address') && $this->address && $this->address->country) {
            $country = strtoupper($this->address->country);
            return $countryToLocaleMap[$country] ?? config('app.fallback_locale', 'en');
        }

        // Return default language if we can't determine the country
        return config('app.fallback_locale', 'en');
    }

    /**
     * Convert country to preferred language or return default
     *
     * @param string|null $country
     * @return string
     */
    public static function localeFromCountry(?string $country): string
    {
        if (!$country) {
            return config('app.fallback_locale', 'en');
        }

        $countryToLocaleMap = [
            'CZ' => 'cs',
            'CS' => 'cs',
            'SK' => 'sk',
            'DE' => 'de',
            'AT' => 'at',
            'CH' => 'ch',
            // Other countries
        ];

        return $countryToLocaleMap[strtoupper($country)] ?? config('app.fallback_locale', 'en');
    }
}
