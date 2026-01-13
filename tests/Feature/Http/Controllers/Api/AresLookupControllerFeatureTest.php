<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AresLookupControllerFeatureTest extends TestCase
{
    #[Test]
    public function invalid_ico_returns_error(): void
    {
        $response = $this->getJson(route('api.ares-lookup', ['ico' => '123']));
        $response->assertOk()
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['success', 'message']);
    }

    #[Test]
    public function not_found_returns_not_found_message(): void
    {
        Http::fake([
            'https://ares.gov.cz/*' => Http::response([
                'zaznamy' => [],
                'popis' => 'Nenalezeno'
            ], 200),
        ]);

        $response = $this->getJson(route('api.ares-lookup', ['ico' => '12345678']));
        $response->assertOk()
            ->assertJson([
                'success' => false,
            ]);
    }

    #[Test]
    public function api_error_returns_custom_message(): void
    {
        Http::fake([
            'https://ares.gov.cz/*' => Http::response([
                'popis' => 'Dočasná chyba'
            ], 500),
        ]);

        $response = $this->getJson(route('api.ares-lookup', ['ico' => '12345678']));
        $response->assertOk()
            ->assertJson([
                'success' => false,
            ]);
    }

    #[Test]
    public function successful_lookup_returns_transformed_data(): void
    {
        Http::fake([
            'https://ares.gov.cz/*' => Http::response([
                'zaznamy' => [[
                    'obchodniJmeno' => 'Test Company s.r.o.',
                    'sidlo' => [
                        'nazevUlice' => 'Hlavni',
                        'cisloDomovni' => '123',
                        'nazevObce' => 'Praha',
                        'nazevCastiObce' => 'Vinohrady',
                        'psc' => '11000',
                        'kodStatu' => 'CZ'
                    ]
                ]]
            ], 200),
        ]);

        $ico = '12345678';
        $response = $this->getJson(route('api.ares-lookup', ['ico' => $ico]));
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'ico' => $ico,
                    'name' => 'Test Company s.r.o.',
                    'street' => 'Hlavni 123',
                    'zip' => '110 00',
                    'dic' => 'CZ' . $ico,
                    'country' => 'CZ'
                ]
            ])
            ->assertJsonPath('data.city', 'Praha, Vinohrady');
    }

    #[Test]
    public function exception_returns_general_error(): void
    {
        Http::fake([
            'https://ares.gov.cz/*' => function () {
                throw new \Exception('Boom');
            },
        ]);

        $response = $this->getJson(route('api.ares-lookup', ['ico' => '12345678']));
        $response->assertOk()
            ->assertJson([
                'success' => false,
            ]);
    }
}
