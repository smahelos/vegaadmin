<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\CountryController;
use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CountryControllerTest extends TestCase
{
    private CountryController $controller;
    private FakeCountryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FakeCountryService();
        $this->controller = new CountryController($this->service);
    }

    #[Test]
    public function controller_instantiates(): void
    {
        $this->assertInstanceOf(CountryController::class, $this->controller);
    }

    #[Test]
    public function get_countries_returns_service_data(): void
    {
        $this->service->countriesForSelect = [ 'CZ' => ['code'=>'CZ','name'=>'Czech Republic','flag'=>'🇨🇿'] ];
        $resp = $this->controller->getCountries();
        $this->assertEquals(200, $resp->status());
        $this->assertArrayHasKey('CZ', $resp->getData(true));
    }

    #[Test]
    public function get_countries_fallback_when_empty(): void
    {
        $this->service->countriesForSelect = []; // triggers controller fallback
        $resp = $this->controller->getCountries();
        $data = $resp->getData(true);
        $this->assertArrayHasKey('CZ', $data);
        $this->assertArrayHasKey('US', $data);
    }

    #[Test]
    public function get_country_found(): void
    {
        $this->service->countryByCode = ['cca2'=>'CZ','name'=>['common'=>'Czech Republic'],'flag'=>'🇨🇿'];
        $resp = $this->controller->getCountry('CZ');
        $this->assertEquals(200, $resp->status());
        $this->assertEquals('CZ', $resp->getData(true)['cca2']);
    }

    #[Test]
    public function get_country_not_found_returns_404(): void
    {
        $this->service->countryByCode = null;
        $resp = $this->controller->getCountry('XX');
        $this->assertEquals(404, $resp->status());
        $this->assertEquals('Country not found', $resp->getData(true)['error']);
    }
}

class FakeCountryService implements CountryApplicationServiceInterface
{
    public array $all = [];
    public array $countriesForSelect = [];
    public ?array $countryByCode = null;

    public function getCountries(): array { return $this->all; }
    public function getCountryCodesForSelect(): array { return []; }
    public function getCountry(string $code): ?array { return $this->countryByCode; }
}
