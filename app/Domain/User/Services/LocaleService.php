<?php

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\Locale;
use App\Domain\User\Contracts\LocaleServiceInterface;

class LocaleService implements LocaleServiceInterface
{
    public function __construct(
        private readonly Locale $locale,
    ) {}

    /**
     * Map country code to preferred locale using configuration.
     * Falls back to app.fallback_locale when not found or empty.
     */
    public function localeFromCountry(?string $country): string
    {
        $fallback = $this->locale->getFallbackLocale();
        if (! $country) {
            return $fallback;
        }
        $map = $this->locale->getCountryLocaleMap();
        $code = strtoupper($country);

        return $map[$code] ?? $fallback;
    }
}
