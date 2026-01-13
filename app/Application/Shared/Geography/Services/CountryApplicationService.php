<?php

namespace App\Application\Shared\Geography\Services;

use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface;
use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;

class CountryApplicationService implements CountryApplicationServiceInterface
{
    public function __construct(private readonly CountryServiceInterface $countryService)
    {
    }

    public function getCountries(): array
    {
        return $this->countryService->getCountriesForSelect();
    }

    public function getCountry(string $code): ?array
    {
        return $this->countryService->getCountryByCode($code);
    }

    public function getCountryCodesForSelect(): array
    {
        return $this->countryService->getCountryCodesForSelect();
    }
}
