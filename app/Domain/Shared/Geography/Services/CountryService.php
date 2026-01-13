<?php

namespace App\Domain\Shared\Geography\Services;

use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Http\Contracts\HttpClientInterface;

class CountryService implements CountryServiceInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheServiceInterface $cacheService
    )
    {
    }
    
    /**
     * Base URL for external country API.
     * @var string
     */
    protected string $apiUrl = 'https://restcountries.com/v3.1';

    /**
     * Retrieve full list of countries from external API (with caching) or fallback list.
     * Ensures consistent structure and alphabetical ordering by common name.
     *
     * @return array<int, array{
     *     cca2: string,
     *     name: array{common: string},
     *     flag: string
     * }>
     */
    public function getAllCountries(): array
    {
        return $this->cacheService->remember('countries_all', function () {
            try {
                $response = $this->httpClient->get("{$this->apiUrl}/all?fields=name,cca2,flag", ['timeout' => 5]);
                if (!empty($response) && is_array($response)) {
                    usort($response, function ($a, $b) { return $a['name']['common'] <=> $b['name']['common']; });
                    return $response;
                }
                return $this->getFallbackCountryCodes();
            } catch (\Exception $e) {
                return $this->getFallbackCountryCodes();
            }
        }, 86400); // Cache for 24 hours
    }

    /**
     * Fallback country data when external API fails.
     *
     * @return array<int, array{
     *     cca2: string,
     *     name: array{common: string},
     *     flag: string
     * }>
     */
    private function getFallbackCountryCodes(): array
    {
        return [
            ['cca2' => 'CZ', 'name' => ['common' => 'Czech Republic'], 'flag' => '🇨🇿'],
            ['cca2' => 'SK', 'name' => ['common' => 'Slovakia'], 'flag' => '🇸🇰'],
            ['cca2' => 'AT', 'name' => ['common' => 'Austria'], 'flag' => '🇦🇹'],
            ['cca2' => 'DE', 'name' => ['common' => 'Germany'], 'flag' => '🇩🇪'],
            ['cca2' => 'PL', 'name' => ['common' => 'Poland'], 'flag' => '🇵🇱'],
            ['cca2' => 'GB', 'name' => ['common' => 'United Kingdom'], 'flag' => '🇬🇧'],
            ['cca2' => 'US', 'name' => ['common' => 'United States'], 'flag' => '🇺🇸'],
        ];
    }

    /**
     * Get countries formatted for detailed select usage (code, name, flag) keyed by ISO code.
     * Falls back to internal static dataset when external data invalid or empty.
     *
     * @return array<string, array{code: string, name: string, flag: string}>
     */
    public function getCountriesForSelect(): array
    {
        $countries = $this->getAllCountries();
        $result = [];
        if (empty($countries)) { return $this->getFallbackCountryCodesFormatted(); }
        foreach ($countries as $country) {
            if (isset($country['cca2'], $country['name']['common'], $country['flag'])) {
                $result[$country['cca2']] = [ 'code' => $country['cca2'], 'name' => $country['name']['common'], 'flag' => $country['flag'] ];
            }
        }
        if (empty($result)) { return $this->getFallbackCountryCodesFormatted(); }
        return $result;
    }

    /**
     * Fallback formatted data keyed by ISO code.
     *
     * @return array<string, array{code: string, name: string, flag: string}>
     */
    private function getFallbackCountryCodesFormatted(): array
    {
        return [
            'CZ' => ['code' => 'CZ', 'name' => 'Czech Republic', 'flag' => '🇨🇿'],
            'SK' => ['code' => 'SK', 'name' => 'Slovakia', 'flag' => '🇸🇰'],
            'AT' => ['code' => 'AT', 'name' => 'Austria', 'flag' => '🇦🇹'],
            'DE' => ['code' => 'DE', 'name' => 'Germany', 'flag' => '🇩🇪'],
            'PL' => ['code' => 'PL', 'name' => 'Poland', 'flag' => '🇵🇱'],
            'GB' => ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'US' => ['code' => 'US', 'name' => 'United States', 'flag' => '🇺🇸'],
        ];
    }

    /**
     * Simplified mapping code => name for select widgets without flag usage.
     *
     * @return array<string,string>
     */
    public function getSimpleCountriesForSelect(): array
    {
        $countries = $this->getAllCountries();
        $result = [];
        foreach ($countries as $country) {
            if (isset($country['cca2'], $country['name']['common'])) { $result[$country['cca2']] = $country['name']['common']; }
        }
        return $result;
    }

    /**
     * Code => name list sorted ascending by country name.
     *
     * @return array<string,string>
     */
    public function getCountryCodesForSelect(): array
    {
        $countries = $this->getAllCountries();
        $result = [];
        foreach ($countries as $country) {
            if (isset($country['cca2'], $country['name']['common'])) { $result[$country['cca2']] = $country['name']['common']; }
        }
        asort($result);
        return $result;
    }

    /**
     * Retrieve detailed country information by ISO alpha-2 code.
     * Uses cache per country for performance and resilience.
     * Returns null if the country cannot be retrieved.
     *
     * @param string $code ISO alpha-2 country code (e.g. "CZ", "US").
     * @return array|null Array matching structure from getAllCountries() or null.
     */
    public function getCountryByCode(string $code): ?array
    {
        $code = strtoupper($code);
        return $this->cacheService->remember("country_{$code}", function () use ($code) {
            try {
                $response = $this->httpClient->get("{$this->apiUrl}/alpha/{$code}");
                if (!empty($response) && is_array($response)) {
                    return $response[0] ?? null;
                }
                return null;
            } catch (\Exception $e) { 
                return null; 
            }
        }, 86400);
    }
}
