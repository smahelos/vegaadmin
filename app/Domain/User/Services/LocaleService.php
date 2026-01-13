<?php

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\LocaleServiceInterface;
use App\Domain\User\Contracts\Locale;
use App\Domain\Shared\Session\Contracts\SessionInterface;

class LocaleService implements LocaleServiceInterface
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly Locale $locale
    )
    {
    }

    /**
     * Get available locales from config.
     *
     * @return array<string>
     */
    public function getAvailableLocales(): array
    {
        return $this->locale->getAvailableLocales();
    }

    /**
     * Determine best locale based on various inputs.
     *
     * Priority: request -> data -> session -> fallback.
     *
     * @param string|null $requestLocale Locale from request
     * @param string|null $dataLocale Locale from stored data (e.g. cached invoice)
     * @return string
     */
    public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string
    {
        return $this->locale->determineLocale($requestLocale, $dataLocale);
    }

    /**
     * Map country code to preferred locale using configuration.
     * Falls back to app.fallback_locale when not found or empty.
     */
    public function localeFromCountry(?string $country): string
    {
        $fallback = $this->locale->getFallbackLocale();
        if (!$country) {
            return $fallback;
        }
        $map = $this->locale->getCountryLocaleMap();
        $code = strtoupper($country);
        return $map[$code] ?? $fallback;
    }

    /**
     * Set application locale and update session.
     * Falls back to configured fallback locale when invalid.
     *
     * @param string $locale Locale to set
     * @return void
     */
    public function setLocale(string $locale): void
    {
        $availableLocales = $this->getAvailableLocales();

        if (in_array($locale, $availableLocales, true)) {
            $this->locale->setLocale($locale);
            $this->session->put('locale', $locale);
            return;
        }

        $fallbackLocale = $this->locale->getFallbackLocale();
        $this->locale->setLocale($fallbackLocale);
        $this->session->put('locale', $fallbackLocale);
    }

    public function getFallbackLocale(): string
    {
        return $this->locale->getFallbackLocale();
    }

    public function getLocale(): string
    {
        return $this->locale->getLocale();
    }

    public function getCountryLocaleMap(): array
    {
        return $this->locale->getCountryLocaleMap();
    }
}
