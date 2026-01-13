<?php

namespace App\Domain\Shared\Geography\Contracts;

interface CountryServiceInterface
{
    /**
     * Get list of all countries retrieved from external API (or fallback list).
     * Each country item contains ISO alpha-2 code, common name and flag emoji.
     *
     * Example item structure:
     * [
     *   'cca2' => 'CZ',
     *   'name' => ['common' => 'Czech Republic'],
     *   'flag' => '🇨🇿'
     * ]
     *
     * @return array<int, array{
     *     cca2: string,
     *     name: array{common: string},
     *     flag: string
     * }>
     */
    public function getAllCountries(): array;

    /**
     * Get countries prepared for select elements with detailed data.
     * Array keyed by country code, values contain code, name and flag.
     *
     * @return array<string, array{code: string, name: string, flag: string}>
     */
    public function getCountriesForSelect(): array;

    /**
     * Get simplified mapping for select elements (code => name).
     *
     * @return array<string, string>
     */
    public function getSimpleCountriesForSelect(): array;

    /**
     * Get mapping of country codes to country names (sorted by name asc).
     *
     * @return array<string, string>
     */
    public function getCountryCodesForSelect(): array;

    /**
     * Get detailed country information by ISO alpha-2 code.
     * Returns null when the country cannot be found.
     *
     * @param string $code ISO alpha-2 country code (e.g. "CZ", "US").
     * @return array|null Array structure identical to items returned by getAllCountries() or null if not found.
     */
    public function getCountryByCode(string $code): ?array;
}
