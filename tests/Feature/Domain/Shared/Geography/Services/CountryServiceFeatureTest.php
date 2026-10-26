<?php

namespace Tests\Feature\Domain\Shared\Geography\Services;

use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CountryServiceFeatureTest extends TestCase
{
    private CountryServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(CountryServiceInterface::class);
        Cache::flush();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CountryServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_countries_for_select_returns_formatted_data(): void
    {
        Http::fake(['restcountries.com/*' => Http::response([
            [
                'cca2' => 'CZ',
                'name' => ['common' => 'Czech Republic'],
                'flag' => '🇨🇿',
            ],
        ], 200)]);
        $result = $this->service->getCountriesForSelect();
        $this->assertArrayHasKey('CZ', $result);
        $this->assertEquals('Czech Republic', $result['CZ']['name']);
    }

    #[Test]
    public function get_countries_for_select_handles_empty_api_response(): void
    {
        Http::fake(['restcountries.com/*' => Http::response([], 200)]);
        $result = $this->service->getCountriesForSelect();
        $this->assertArrayHasKey('CZ', $result); // Fallback
    }

    #[Test]
    public function get_country_codes_for_select_returns_sorted_data(): void
    {
        Http::fake(['restcountries.com/*' => Http::response([
            [
                'cca2' => 'SK',
                'name' => ['common' => 'Slovakia'],
                'flag' => '🇸🇰',
            ],
            [
                'cca2' => 'CZ',
                'name' => ['common' => 'Czech Republic'],
                'flag' => '🇨🇿',
            ],
        ], 200)]);
        $result = $this->service->getCountryCodesForSelect();
        $keys = array_keys($result);
        $this->assertLessThan(array_search('SK', $keys), array_search('CZ', $keys));
    }

    #[Test]
    public function get_country_by_code_returns_country_data(): void
    {
        Http::fake(['restcountries.com/v3.1/alpha/CZ' => Http::response([
            [
                'cca2' => 'CZ',
                'name' => ['common' => 'Czech Republic'],
                'flag' => '🇨🇿',
            ],
        ], 200)]);
        $result = $this->service->getCountryByCode('CZ');
        $this->assertEquals('CZ', $result['cca2']);
    }

    #[Test]
    public function get_country_by_code_handles_lowercase_input(): void
    {
        Http::fake(['restcountries.com/v3.1/alpha/CZ' => Http::response([
            ['cca2' => 'CZ', 'name' => ['common' => 'Czech Republic']],
        ], 200)]);
        $result = $this->service->getCountryByCode('cz');
        $this->assertEquals('CZ', $result['cca2']);
    }

    #[Test]
    public function get_country_by_code_returns_null_for_invalid_code(): void
    {
        Http::fake(['restcountries.com/*' => Http::response([], 404)]);
        $this->assertNull($this->service->getCountryByCode('INVALID'));
    }

    #[Test]
    public function get_country_by_code_caches_result(): void
    {
        Http::fake(['restcountries.com/v3.1/alpha/CZ' => Http::response([
            ['cca2' => 'CZ', 'name' => ['common' => 'Czech Republic']],
        ], 200)]);
        $r1 = $this->service->getCountryByCode('CZ');
        $r2 = $this->service->getCountryByCode('CZ');
        $this->assertEquals($r1, $r2);
        Http::assertSentCount(1);
    }
}
