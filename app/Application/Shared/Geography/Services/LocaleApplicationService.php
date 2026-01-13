<?php

namespace App\Application\Shared\Geography\Services;

use App\Application\Shared\Geography\Contracts\LocaleApplicationServiceInterface;
use App\Domain\User\Contracts\Locale;

class LocaleApplicationService implements LocaleApplicationServiceInterface
{
    public function __construct(private readonly Locale $locale)
    {
    }

    /**
     * Determine best locale based on various inputs.
     *
     * @param string|null $requestLocale Locale from request
     * @param string|null $dataLocale Locale from data
     * @return string
     */
    public function determineLocale(?string $requestLocale = null, ?string $dataLocale = null): string
    {
        return $this->locale->determineLocale($requestLocale, $dataLocale);
    }
}
