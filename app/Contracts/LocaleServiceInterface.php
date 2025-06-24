<?php

namespace App\Contracts;

interface LocaleServiceInterface
{
    /**
     * Get available locales from config
     * 
     * @return array
     */
    public function getAvailableLocales(): array;

    /**
     * Determine best locale based on various inputs
     * 
     * @param string|null $requestLocale Locale from request
     * @param string|null $dataLocale Locale from data
     * @return string
     */
    public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string;

    /**
     * Set application locale and update session
     * 
     * @param string $locale Locale to set
     * @return void
     */
    public function setLocale(string $locale): void;
}
