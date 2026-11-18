<?php

declare(strict_types=1);

namespace App\Application\Shared\Geography\Services;

use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;

final class CountryApplicationService
{
    public function __construct(private readonly CountryServiceInterface $countryService) {}

    /** @return array<string, array{code:string, name:string, flag:string}> */
    public function getCountries(): array
    {
        return $this->countryService->getCountriesForSelect();
    }

    public function getCountry(string $code): ?array
    {
        return $this->countryService->getCountryByCode($code);
    }
}
