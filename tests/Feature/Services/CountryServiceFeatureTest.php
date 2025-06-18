<?php

namespace Tests\Feature\Services;

use App\Services\CountryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CountryServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CountryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CountryService();
        
        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CountryService::class, $this->service);
    }

    #[Test]
    public function get_all_countries_returns_fallback_when_api_fails(): void
    {
        // Mock failed HTTP response
        Http::fake([
            'restcountries.com/*' => Http::response([], 500)
        ]);

        $result = $this->service->getAllCountries();
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Should contain fallback data
        $foundCZ = false;
        foreach ($result as $country) {
            if (isset($country['cca2']) && $country['cca2'] === 'CZ') {
                $foundCZ = true;
                $this->assertEquals('Czech Republic', $country['name']['common']);
                $this->assertEquals('🇨🇿', $country['flag']);
                break;
            }
        }
        
        $this->assertTrue($foundCZ, 'Czech Republic not found in fallback data');
    }

    #[Test]
    public function get_all_countries_returns_api_data_when_successful(): void
    {
        // Mock successful HTTP response
        $mockData = [
            [
                'cca2' => 'CZ',
                'name' => ['common' => 'Czech Republic'],
                'flag' => '🇨🇿'
            ],
            [
                'cca2' => 'SK',
                'name' => ['common' => 'Slovakia'], 
                'flag' => '🇸🇰'
            ]
        ];
        
        Http::fake([
            'restcountries.com/*' => Http::response($mockData, 200)
        ]);

        $result = $this->service->getAllCountries();
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('CZ', $result[0]['cca2']);
        $this->assertEquals('SK', $result[1]['cca2']);
    }

    #[Test]
    public function get_all_countries_caches_result(): void
    {
        // Mock successful HTTP response
        Http::fake([
            'restcountries.com/*' => Http::response([
                ['cca2' => 'CZ', 'name' => ['common' => 'Czech Republic'], 'flag' => '🇨🇿']
            ], 200)
        ]);

        // First call
        $result1 = $this->service->getAllCountries();
        
        // Second call should use cache
        $result2 = $this->service->getAllCountries();
        
        $this->assertEquals($result1, $result2);
        
        // Verify cache was used by checking that HTTP was only called once
        Http::assertSentCount(1);
    }

    #[Test]
    public function get_countries_for_select_returns_formatted_data(): void
    {
        // Mock API response
        Http::fake([
            'restcountries.com/*' => Http::response([
                [
                    'cca2' => 'CZ',
                    'name' => ['common' => 'Czech Republic'],
                    'flag' => '🇨🇿'
                ]
            ], 200)
        ]);

        $result = $this->service->getCountriesForSelect();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('CZ', $result);
        $this->assertEquals('CZ', $result['CZ']['code']);
        $this->assertEquals('Czech Republic', $result['CZ']['name']);
        $this->assertEquals('🇨🇿', $result['CZ']['flag']);
    }

    #[Test]
    public function get_countries_for_select_handles_empty_api_response(): void
    {
        // Mock empty API response
        Http::fake([
            'restcountries.com/*' => Http::response([], 200)
        ]);

        $result = $this->service->getCountriesForSelect();
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Should contain fallback formatted data
        $this->assertArrayHasKey('CZ', $result);
        $this->assertEquals('Czech Republic', $result['CZ']['name']);
    }

    #[Test]
    public function get_simple_countries_for_select_returns_code_name_pairs(): void
    {
        // Mock API response
        Http::fake([
            'restcountries.com/*' => Http::response([
                [
                    'cca2' => 'CZ',
                    'name' => ['common' => 'Czech Republic'],
                    'flag' => '🇨🇿'
                ],
                [
                    'cca2' => 'SK',
                    'name' => ['common' => 'Slovakia'],
                    'flag' => '🇸🇰'
                ]
            ], 200)
        ]);

        $result = $this->service->getSimpleCountriesForSelect();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('CZ', $result);
        $this->assertArrayHasKey('SK', $result);
        $this->assertEquals('Czech Republic', $result['CZ']);
        $this->assertEquals('Slovakia', $result['SK']);
    }

    #[Test]
    public function get_country_codes_for_select_returns_sorted_data(): void
    {
        // Mock API response with unsorted data
        Http::fake([
            'restcountries.com/*' => Http::response([
                [
                    'cca2' => 'SK',
                    'name' => ['common' => 'Slovakia'],
                    'flag' => '🇸🇰'
                ],
                [
                    'cca2' => 'CZ',
                    'name' => ['common' => 'Czech Republic'],
                    'flag' => '🇨🇿'
                ]
            ], 200)
        ]);

        $result = $this->service->getCountryCodesForSelect();
        
        $this->assertIsArray($result);
        
        // Check that data is sorted by country name (Czech Republic should come before Slovakia)
        $keys = array_keys($result);
        $values = array_values($result);
        
        $czIndex = array_search('CZ', $keys);
        $skIndex = array_search('SK', $keys);
        
        $this->assertNotFalse($czIndex);
        $this->assertNotFalse($skIndex);
        $this->assertLessThan($skIndex, $czIndex); // Czech Republic comes before Slovakia alphabetically
    }

    #[Test]
    public function get_country_by_code_returns_country_data(): void
    {
        $mockCountryData = [
            'cca2' => 'CZ',
            'name' => ['common' => 'Czech Republic'],
            'capital' => ['Prague'],
            'flag' => '🇨🇿'
        ];
        
        // Mock API response
        Http::fake([
            'restcountries.com/v3.1/alpha/CZ' => Http::response([$mockCountryData], 200)
        ]);

        $result = $this->service->getCountryByCode('CZ');
        
        $this->assertIsArray($result);
        $this->assertEquals('CZ', $result['cca2']);
        $this->assertEquals('Czech Republic', $result['name']['common']);
    }

    #[Test]
    public function get_country_by_code_handles_lowercase_input(): void
    {
        $mockCountryData = [
            'cca2' => 'CZ',
            'name' => ['common' => 'Czech Republic']
        ];
        
        Http::fake([
            'restcountries.com/v3.1/alpha/CZ' => Http::response([$mockCountryData], 200)
        ]);

        $result = $this->service->getCountryByCode('cz'); // lowercase input
        
        $this->assertIsArray($result);
        $this->assertEquals('CZ', $result['cca2']);
    }

    #[Test]
    public function get_country_by_code_returns_null_for_invalid_code(): void
    {
        // Mock failed API response
        Http::fake([
            'restcountries.com/*' => Http::response([], 404)
        ]);

        $result = $this->service->getCountryByCode('INVALID');
        
        $this->assertNull($result);
    }

    #[Test]
    public function get_country_by_code_caches_result(): void
    {
        $mockCountryData = [
            'cca2' => 'CZ',
            'name' => ['common' => 'Czech Republic']
        ];
        
        Http::fake([
            'restcountries.com/v3.1/alpha/CZ' => Http::response([$mockCountryData], 200)
        ]);

        // First call
        $result1 = $this->service->getCountryByCode('CZ');
        
        // Second call should use cache
        $result2 = $this->service->getCountryByCode('CZ');
        
        $this->assertEquals($result1, $result2);
        
        // Verify cache was used
        Http::assertSentCount(1);
    }

    #[Test]
    public function methods_handle_api_timeout(): void
    {
        // Mock API timeout
        Http::fake([
            'restcountries.com/*' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        // getAllCountries should return fallback data
        $result = $this->service->getAllCountries();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // getCountryByCode should return null
        $countryResult = $this->service->getCountryByCode('CZ');
        $this->assertNull($countryResult);
    }
}
