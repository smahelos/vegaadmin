<?php

namespace App\Contracts;

interface CountryServiceInterface
{
    /**
     * Get list of all countries
     * 
     * @return array
     */
    public function getAllCountries(): array;

    /**
     * Get countries formatted for select dropdown
     * 
     * @return array Array with country codes as keys and country names as values
     */
    public function getCountriesForSelect(): array;

    /**
     * Get simplified list of countries for select dropdown
     * 
     * @return array Array with country codes as keys and simplified names as values
     */
    public function getSimpleCountriesForSelect(): array;

    /**
     * Get country codes for select dropdown
     * 
     * @return array Array with country codes as both keys and values
     */
    public function getCountryCodesForSelect(): array;

    /**
     * Get country information by country code
     * 
     * @param string $code Country code (e.g., 'US', 'CZ')
     * @return array|null Country data or null if not found
     */
    public function getCountryByCode(string $code): ?array;
}
