<?php

namespace App\Traits;

trait HasPreferredLocale
{
    /**
     * Určí preferovaný jazyk na základě země nebo vrátí výchozí jazyk
     *
     * @return string
     */
    public function getPreferredLocale(): string
    {
        // Mapování zemí na jazyky
        $countryToLocaleMap = [
            'CZ' => 'cs',
            'CS' => 'cs',
            'SK' => 'sk',
            'DE' => 'de',
            'AT' => 'de', // Rakousko používá němčinu
            'CH' => 'de', // Švýcarsko (můžete upřesnit podle regionu)
            // Přidejte další země podle potřeby
        ];

        // Pokud má model pole country, použijeme ho přímo
        if (isset($this->attributes['country'])) {
            $country = strtoupper($this->attributes['country']);
            return $countryToLocaleMap[$country] ?? config('app.fallback_locale', 'en');
        }

        // Pro případy, kdy je country v jiném vztahu (např. address)
        if (method_exists($this, 'address') && $this->address && $this->address->country) {
            $country = strtoupper($this->address->country);
            return $countryToLocaleMap[$country] ?? config('app.fallback_locale', 'en');
        }

        // Vrátíme výchozí jazyk, pokud nemůžeme určit zemi
        return config('app.fallback_locale', 'en');
    }

    /**
     * Převeďte zemi na preferovaný jazyk nebo vraťte výchozí
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
            'AT' => 'de',
            'CH' => 'de',
            // Další země
        ];

        return $countryToLocaleMap[strtoupper($country)] ?? config('app.fallback_locale', 'en');
    }
}
