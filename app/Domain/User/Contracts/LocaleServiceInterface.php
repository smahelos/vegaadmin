<?php

namespace App\Domain\User\Contracts;

interface LocaleServiceInterface
{
    /**
     * Map country code to preferred locale using configuration.
     * Falls back to app.fallback_locale when not found or empty.
     */
    public function localeFromCountry(?string $country): string;
}
