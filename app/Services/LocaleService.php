<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class LocaleService
{
    /**
     * Get available locales from config
     * 
     * @return array
     */
    public function getAvailableLocales(): array
    {
        return config('app.available_locales', ['cs', 'en', 'de', 'sk']);
    }

    /**
     * Determine best locale based on various inputs
     * 
     * @param string|null $requestLocale Locale from request
     * @param string|null $dataLocale Locale from data
     * @return string
     */
    public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string
    {
        $availableLocales = $this->getAvailableLocales();
        $fallbackLocale = config('app.fallback_locale', 'cs');
        
        if ($requestLocale && in_array($requestLocale, $availableLocales)) {
            return $requestLocale;
        }
        
        if ($dataLocale && in_array($dataLocale, $availableLocales)) {
            return $dataLocale;
        }
        
        $sessionLocale = Session::get('locale');
        if ($sessionLocale && in_array($sessionLocale, $availableLocales)) {
            return $sessionLocale;
        }
        
        return $fallbackLocale;
    }

    /**
     * Set application locale and update session
     * 
     * @param string $locale Locale to set
     * @return void
     */
    public function setLocale(string $locale): void
    {
        $availableLocales = $this->getAvailableLocales();
        
        if (in_array($locale, $availableLocales)) {
            app()->setLocale($locale);
            Session::put('locale', $locale);
        } else {
            $fallbackLocale = config('app.fallback_locale', 'cs');
            app()->setLocale($fallbackLocale);
            Session::put('locale', $fallbackLocale);
        }
    }
}
