<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\AresLookupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AresLookupControllerTest extends TestCase
{
    private AresLookupController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AresLookupController();
    }

    #[Test]
    public function controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(AresLookupController::class, $this->controller);
    }

    #[Test]
    public function lookup_method_exists_with_request_parameter(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('lookup'));
        $method = $reflection->getMethod('lookup');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('request', $params[0]->getName());
    }

    #[Test]
    public function invalid_ico_returns_error(): void
    {
        $request = Request::create('/api/ares-lookup', 'GET', ['ico' => '123']);
        $response = $this->controller->lookup($request);
        $json = $response->getData(true);
        $this->assertFalse($json['success']);
        $this->assertArrayHasKey('message', $json);
    }

    #[Test]
    public function not_found_when_empty_records(): void
    {
        Http::fake([
            'https://ares.gov.cz/*' => Http::response([
                'zaznamy' => [],
                'popis' => 'Nenalezeno'
            ], 200)
        ]);
        $request = Request::create('/api/ares-lookup', 'GET', ['ico' => '12345678']);
        $response = $this->controller->lookup($request);
        $json = $response->getData(true);
        $this->assertFalse($json['success']);
        $this->assertArrayHasKey('message', $json);
    }

    #[Test]
    public function api_error_propagated(): void
    {
        Http::fake([
            'https://ares.gov.cz/*' => Http::response(['popis' => 'Chyba'], 500)
        ]);
        $request = Request::create('/api/ares-lookup', 'GET', ['ico' => '12345678']);
        $response = $this->controller->lookup($request);
        $json = $response->getData(true);
        $this->assertFalse($json['success']);
        $this->assertEquals('Chyba', $json['message']);
    }

    #[Test]
    public function successful_lookup_transforms_data(): void
    {
        Http::fake([
            'https://ares.gov.cz/*' => Http::response([
                'zaznamy' => [[
                    'obchodniJmeno' => 'Demo Company s.r.o.',
                    'sidlo' => [
                        'nazevUlice' => 'Nova',
                        'cisloDomovni' => '77',
                        'nazevObce' => 'Brno',
                        'nazevCastiObce' => 'Brno-stred',
                        'psc' => '60200',
                        'kodStatu' => 'CZ'
                    ]
                ]]
            ], 200)
        ]);
        $ico = '87654321';
        $request = Request::create('/api/ares-lookup', 'GET', ['ico' => $ico]);
        $response = $this->controller->lookup($request);
        $json = $response->getData(true);
        $this->assertTrue($json['success']);
        $this->assertEquals('Demo Company s.r.o.', $json['data']['name']);
        $this->assertEquals('Nova 77', $json['data']['street']);
        $this->assertEquals('602 00', $json['data']['zip']);
        $this->assertEquals('CZ'.$ico, $json['data']['dic']);
        $this->assertEquals('Brno, Brno-stred', $json['data']['city']);
    }

    #[Test]
    public function exception_returns_general_error(): void
    {
        Http::fake([
            'https://ares.gov.cz/*' => function () { throw new \Exception('Failure'); }
        ]);
        $request = Request::create('/api/ares-lookup', 'GET', ['ico' => '12345678']);
        $response = $this->controller->lookup($request);
        $json = $response->getData(true);
        $this->assertFalse($json['success']);
        $this->assertArrayHasKey('message', $json);
    }
}
