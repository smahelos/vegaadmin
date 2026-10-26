<?php

namespace App\Application\Shared\Geography\Contracts;

interface LocaleApplicationServiceInterface
{
    /**
     * Determine best locale based on various inputs.
     */
    public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string;
}
