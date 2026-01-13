<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;

class CountryControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_countries_list_with_expected_structure(): void
    {
        Http::fake(); // ensure external API not actually called
        Cache::forget('countries_all');

        $response = $this->getJson(route('api.countries'));
        $response->assertOk();
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('CZ', $data); // fallback list present
        $this->assertEquals(['code','name','flag'], array_keys($data['CZ']));
    }

    #[Test]
    public function it_returns_country_detail_when_found(): void
    {
        Http::fake([
            'restcountries.com/*' => Http::response([
                [ 'cca2' => 'CZ', 'name' => ['common' => 'Czech Republic'], 'flag' => '\u{1F1E8}\u{1F1FF}' ]
            ], 200)
        ]);
        Cache::forget('country_CZ');

        $resp = $this->getJson(route('api.country', ['code' => 'CZ']));
        $resp->assertOk();
        $this->assertEquals('CZ', $resp->json('cca2'));
    }

    #[Test]
    public function it_returns_404_for_unknown_country_code(): void
    {
        Http::fake([ 'restcountries.com/*' => Http::response([], 404) ]);
        Cache::forget('country_XX');
        $resp = $this->getJson(route('api.country', ['code' => 'XX']));
        $resp->assertStatus(404)->assertJson(['error' => 'Country not found']);
    }
}
