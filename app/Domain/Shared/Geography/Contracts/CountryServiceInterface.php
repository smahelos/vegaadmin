<?php

namespace App\Domain\Shared\Geography\Contracts;

interface CountryServiceInterface
{
    /**
     * Get countries prepared for select elements with detailed data.
     * Array keyed by country code, values contain code, name and flag.
     *
     * @return array<string, array{code: string, name: string, flag: string}>
     */
    public function getCountriesForSelect(): array;

    /**
     * Get detailed country information by ISO alpha-2 code.
     * Returns null when the country cannot be found.
     *
     * @param  string  $code  ISO alpha-2 country code (e.g. "CZ", "US").
     * @return array|null Array structure identical to items returned by getAllCountries() or null if not found.
     */
    public function getCountryByCode(string $code): ?array;
}
