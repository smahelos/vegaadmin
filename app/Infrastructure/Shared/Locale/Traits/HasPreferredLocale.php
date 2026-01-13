<?php

namespace App\Infrastructure\Shared\Locale\Traits;

use App\Domain\User\Contracts\LocaleServiceInterface;

/**
 * Infrastructure trait: preferred locale resolution for Eloquent-like models.
 *
 * Note: This trait accesses model attributes/relations and the Laravel container,
 * so it belongs in Infrastructure. Domain logic should depend on LocaleServiceInterface
 * directly instead of using this trait.
 */
trait HasPreferredLocale
{
    /**
     * Determines preferred language based on country or returns default language
     * 
     * @return string
     */
    public function getPreferredLocale(): string
    {
        /** @var LocaleServiceInterface $localeService */
        $localeService = app(LocaleServiceInterface::class);

        // If the model has a country field, use it directly
        if (isset($this->attributes['country']) && $this->attributes['country']) {
            return $localeService->localeFromCountry((string) $this->attributes['country']);
        }

        // For cases where country is in another relationship (e.g. address)
        if (method_exists($this, 'address') && $this->address && $this->address->country) {
            return $localeService->localeFromCountry((string) $this->address->country);
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
        /** @var LocaleServiceInterface $localeService */
        $localeService = app(LocaleServiceInterface::class);
        return $localeService->localeFromCountry($country);
    }
}
