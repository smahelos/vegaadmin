<?php

namespace Tests\Feature\Domain\Invoice\Services;

use App\Domain\Shared\Tax\Services\TaxService as TaxesService;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxesServiceFeatureTest extends TestCase
{
    private TaxesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxesService::class);

        // The service lists the whole taxes table; start without the seeded VAT rates
        Tax::query()->delete();

        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function get_all_taxes_for_select_returns_ordered_taxes_as_id_name_array(): void
    {
        // Arrange
        $tax1 = Tax::factory()->create(['name' => 'Zebra Tax']);
        $tax2 = Tax::factory()->create(['name' => 'Alpha Tax']);
        $tax3 = Tax::factory()->create(['name' => 'Beta Tax']);

        // Act
        $result = $this->service->getAllTaxesForSelect();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Should be ordered by name and use id as key
        $this->assertEquals('Alpha Tax', $result[$tax2->id]);
        $this->assertEquals('Beta Tax', $result[$tax3->id]);
        $this->assertEquals('Zebra Tax', $result[$tax1->id]);

        // Verify keys are IDs not slugs
        $this->assertArrayHasKey($tax1->id, $result);
        $this->assertArrayHasKey($tax2->id, $result);
        $this->assertArrayHasKey($tax3->id, $result);
    }

    #[Test]
    public function get_all_taxes_for_select_returns_empty_array_when_no_taxes(): void
    {
        // Act
        $result = $this->service->getAllTaxesForSelect();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function taxes_for_select_cache_expires_after_5_minutes(): void
    {
        // Arrange
        $t1 = Tax::factory()->create(['name' => 'Test Tax']);

        // Act - First call to populate cache
        $initial = $this->service->getAllTaxesForSelect();
        $this->assertArrayHasKey($t1->id, $initial);

        // Before TTL: add new tax but expect cached result (no new id)
        $this->travel(4)->minutes();
        $t2 = Model::withoutEvents(function () {
            return Tax::factory()->create(['name' => 'New Tax']);
        });
        $beforeExpire = $this->service->getAllTaxesForSelect();
        $this->assertEquals($initial, $beforeExpire);
        $this->assertArrayNotHasKey($t2->id, $beforeExpire);

        // After TTL: expect refreshed result to include new id
        $this->travel(2)->minutes(); // Total 6 minutes
        $afterExpire = $this->service->getAllTaxesForSelect();
        $this->assertArrayHasKey($t2->id, $afterExpire);
    }
}
