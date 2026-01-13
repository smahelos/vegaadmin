<?php

namespace App\Application\Shared\Geography\Contracts;

interface LocaleApplicationServiceInterface
{
    /**
     * Determine best locale based on various inputs.
     *
     * @param string|null $requestLocale Locale from request
     * @param string|null $dataLocale Locale from data
     * @return string
     */
    public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string;
}
