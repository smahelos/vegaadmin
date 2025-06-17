<?php

namespace Tests\Feature\Models;

use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Database\QueryException;

class TaxTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function model_can_be_created_with_factory(): void
    {
        $tax = Tax::factory()->create();

        $this->assertInstanceOf(Tax::class, $tax);
        $this->assertNotNull($tax->id);
        $this->assertNotEmpty($tax->name);
        $this->assertIsNumeric($tax->rate);
        $this->assertNotNull($tax->created_at);
        $this->assertNotNull($tax->updated_at);
    }

    #[Test]
    public function tax_factory_can_create_zero_rate_tax(): void
    {
        $tax = Tax::factory()->zero()->create();

        $this->assertEquals(0.00, $tax->rate);
    }

    #[Test]
    public function tax_factory_can_create_standard_vat(): void
    {
        $tax = Tax::factory()->standardVat()->create();

        $this->assertEquals('Standard VAT', $tax->name);
        $this->assertEquals(21.00, $tax->rate);
        $this->assertEquals('standard-vat', $tax->slug);
    }

    #[Test]
    public function tax_factory_can_create_reduced_vat(): void
    {
        $tax = Tax::factory()->reducedVat()->create();

        $this->assertEquals('Reduced VAT', $tax->name);
        $this->assertEquals(15.00, $tax->rate);
        $this->assertEquals('reduced-vat', $tax->slug);
    }

    #[Test]
    public function can_create_tax_through_database(): void
    {
        $taxData = [
            'name' => 'Test Tax',
            'rate' => 25.50,
            'slug' => 'test-tax',
        ];

        $tax = Tax::create($taxData);

        $this->assertDatabaseHas('taxes', [
            'name' => 'Test Tax',
            'rate' => 25.50,
            'slug' => 'test-tax',
        ]);

        $this->assertEquals('Test Tax', $tax->name);
        $this->assertEquals(25.50, $tax->rate);
        $this->assertEquals('test-tax', $tax->slug);
    }

    #[Test]
    public function can_update_tax(): void
    {
        $tax = Tax::factory()->create([
            'name' => 'Original Tax',
            'rate' => 10.00,
        ]);

        $tax->update([
            'name' => 'Updated Tax',
            'rate' => 20.00,
        ]);

        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'name' => 'Updated Tax',
            'rate' => 20.00,
        ]);

        $this->assertDatabaseMissing('taxes', [
            'id' => $tax->id,
            'name' => 'Original Tax',
        ]);
    }

    #[Test]
    public function can_delete_tax(): void
    {
        $tax = Tax::factory()->create();
        $taxId = $tax->id;

        $tax->delete();

        $this->assertDatabaseMissing('taxes', [
            'id' => $taxId,
        ]);
    }

    #[Test]
    public function name_is_required(): void
    {
        $this->expectException(QueryException::class);
        
        Tax::factory()->create(['name' => null]);
    }

    #[Test]
    public function rate_is_required(): void
    {
        $this->expectException(QueryException::class);
        
        Tax::factory()->create(['rate' => null]);
    }

    #[Test]
    public function can_query_taxes_by_rate_range(): void
    {
        Tax::factory()->create(['name' => 'Low Tax', 'rate' => 5.00]);
        Tax::factory()->create(['name' => 'Medium Tax', 'rate' => 15.00]);
        Tax::factory()->create(['name' => 'High Tax', 'rate' => 25.00]);

        $mediumTaxes = Tax::whereBetween('rate', [10.00, 20.00])->get();

        $this->assertCount(1, $mediumTaxes);
        $this->assertEquals('Medium Tax', $mediumTaxes->first()->name);
    }

    #[Test]
    public function can_order_taxes_by_name(): void
    {
        Tax::factory()->create(['name' => 'Zebra Tax']);
        Tax::factory()->create(['name' => 'Alpha Tax']);
        Tax::factory()->create(['name' => 'Beta Tax']);

        $taxesOrderedByName = Tax::orderBy('name')->pluck('name')->toArray();

        $expectedOrder = ['Alpha Tax', 'Beta Tax', 'Zebra Tax'];
        $this->assertEquals($expectedOrder, $taxesOrderedByName);
    }

    #[Test]
    public function can_order_taxes_by_rate(): void
    {
        Tax::factory()->create(['name' => 'High Tax', 'rate' => 25.00]);
        Tax::factory()->create(['name' => 'Low Tax', 'rate' => 5.00]);
        Tax::factory()->create(['name' => 'Medium Tax', 'rate' => 15.00]);

        $taxesOrderedByRate = Tax::orderBy('rate')->pluck('name')->toArray();

        $expectedOrder = ['Low Tax', 'Medium Tax', 'High Tax'];
        $this->assertEquals($expectedOrder, $taxesOrderedByRate);
    }

    #[Test]
    public function can_search_taxes_by_name(): void
    {
        Tax::factory()->create(['name' => 'Standard VAT']);
        Tax::factory()->create(['name' => 'Reduced VAT']);
        Tax::factory()->create(['name' => 'Zero Rate']);

        $searchResults = Tax::whereRaw('LOWER(name) LIKE ?', ['%vat%'])->get();

        $this->assertCount(2, $searchResults);
        $this->assertTrue($searchResults->pluck('name')->contains('Standard VAT'));
        $this->assertTrue($searchResults->pluck('name')->contains('Reduced VAT'));
        $this->assertFalse($searchResults->pluck('name')->contains('Zero Rate'));
    }

    #[Test]
    public function can_filter_taxes_by_zero_rate(): void
    {
        Tax::factory()->zero()->count(2)->create();
        Tax::factory()->count(3)->create(['rate' => 21.00]);

        $zeroRateTaxes = Tax::where('rate', 0)->get();
        $nonZeroTaxes = Tax::where('rate', '>', 0)->get();

        $this->assertCount(2, $zeroRateTaxes);
        $this->assertCount(3, $nonZeroTaxes);
    }

    #[Test]
    public function can_perform_bulk_operations(): void
    {
        Tax::factory()->count(5)->create(['rate' => 21.00]);

        // Bulk update
        Tax::where('rate', 21.00)->update(['rate' => 23.00]);

        $this->assertEquals(0, Tax::where('rate', 21.00)->count());
        $this->assertEquals(5, Tax::where('rate', 23.00)->count());
    }

    #[Test]
    public function rate_formatted_attribute_works_with_database(): void
    {
        $tax = Tax::factory()->create(['rate' => 21.50]);
        
        $tax->refresh();
        
        $this->assertEquals('21.50%', $tax->rate_formatted);
    }

    #[Test]
    public function tax_has_timestamps(): void
    {
        $tax = Tax::factory()->create();

        $this->assertNotNull($tax->created_at);
        $this->assertNotNull($tax->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tax->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tax->updated_at);
    }

    #[Test]
    public function can_create_tax_with_minimal_data(): void
    {
        $tax = Tax::create([
            'name' => 'Minimal Tax',
            'rate' => 10.00,
        ]);

        $this->assertDatabaseHas('taxes', [
            'name' => 'Minimal Tax',
            'rate' => 10.00,
        ]);

        $this->assertNotNull($tax->id);
        $this->assertEquals('Minimal Tax', $tax->name);
        $this->assertEquals(10.00, $tax->rate);
    }

    #[Test]
    public function rate_is_stored_as_decimal(): void
    {
        $tax = Tax::factory()->create(['rate' => 21.55]);
        
        $tax->refresh();
        
        $this->assertIsNumeric($tax->rate);
        $this->assertEquals(21.55, (float) $tax->rate);
    }

    #[Test]
    public function can_find_tax_by_slug(): void
    {
        $tax = Tax::factory()->create([
            'name' => 'Standard VAT',
            'slug' => 'standard-vat',
        ]);

        $foundTax = Tax::where('slug', 'standard-vat')->first();

        $this->assertNotNull($foundTax);
        $this->assertEquals($tax->id, $foundTax->id);
        $this->assertEquals('Standard VAT', $foundTax->name);
    }
}
