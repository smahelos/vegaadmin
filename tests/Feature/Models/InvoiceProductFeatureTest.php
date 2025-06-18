<?php

namespace Tests\Feature\Models;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceProductFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_invoice_product(): void
    {
        $invoice = Invoice::factory()->create();
        $product = Product::factory()->create();

        $invoiceProduct = InvoiceProduct::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'name' => 'Test Product',
            'quantity' => 2,
            'price' => 100.00,
            'tax_rate' => 21.00
        ]);

        $this->assertDatabaseHas('invoice_products', [
            'id' => $invoiceProduct->id,
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'name' => 'Test Product',
            'quantity' => 2,
            'price' => 100.00,
            'tax_rate' => 21.00
        ]);
    }

    #[Test]
    public function invoice_relationship_works_correctly(): void
    {
        $invoice = Invoice::factory()->create();
        $invoiceProduct = InvoiceProduct::factory()->create(['invoice_id' => $invoice->id]);

        $relationship = $invoiceProduct->invoice();
        $this->assertInstanceOf(BelongsTo::class, $relationship);
        $this->assertEquals($invoice->id, $invoiceProduct->invoice->id);
    }

    #[Test]
    public function product_relationship_works_correctly(): void
    {
        $product = Product::factory()->create();
        $invoiceProduct = InvoiceProduct::factory()->create(['product_id' => $product->id]);

        $relationship = $invoiceProduct->product();
        $this->assertInstanceOf(BelongsTo::class, $relationship);
        $this->assertEquals($product->id, $invoiceProduct->product->id);
    }

    #[Test]
    public function boot_method_calculates_values_on_save(): void
    {
        $invoiceProduct = InvoiceProduct::factory()->make([
            'price' => 100.00,
            'quantity' => 2,
            'tax_rate' => 21.00,
            'tax_amount' => 0, // Will be calculated by boot method
            'total_price' => 0 // Will be calculated by boot method
        ]);

        $invoiceProduct->save();

        // Tax amount should be calculated: 100 * 2 * 21 / 100 = 42
        $this->assertEquals(42.00, $invoiceProduct->tax_amount);
        
        // Total price should be calculated: (100 * 2) + 42 = 242
        $this->assertEquals(242.00, $invoiceProduct->total_price);
    }

    #[Test]
    public function boot_method_recalculates_on_update(): void
    {
        $invoiceProduct = InvoiceProduct::factory()->create([
            'price' => 50.00,
            'quantity' => 1,
            'tax_rate' => 10.00
        ]);

        // Update the values
        $invoiceProduct->update([
            'price' => 200.00,
            'quantity' => 3,
            'tax_rate' => 25.00
        ]);

        // Tax amount should be recalculated: 200 * 3 * 25 / 100 = 150
        $this->assertEquals(150.00, $invoiceProduct->tax_amount);
        
        // Total price should be recalculated: (200 * 3) + 150 = 750
        $this->assertEquals(750.00, $invoiceProduct->total_price);
    }

    #[Test]
    public function calculate_tax_amount_method_works_correctly(): void
    {
        $invoiceProduct = InvoiceProduct::factory()->create([
            'price' => 80.00,
            'quantity' => 5,
            'tax_rate' => 15.00
        ]);

        $result = $invoiceProduct->calculateTaxAmount();

        // Expected: 80 * 5 * 15 / 100 = 60
        $this->assertEquals(60.00, $result);
        $this->assertEquals(60.00, $invoiceProduct->tax_amount);
    }

    #[Test]
    public function calculate_total_price_method_works_correctly(): void
    {
        $invoiceProduct = InvoiceProduct::factory()->create([
            'price' => 120.00,
            'quantity' => 4,
            'tax_rate' => 20.00 // Will be used to calculate tax_amount
        ]);

        // The boot method will calculate tax_amount as: 120 * 4 * 20 / 100 = 96
        $this->assertEquals(96.00, $invoiceProduct->tax_amount);
        
        // Manually call calculateTotalPrice to test the method
        $result = $invoiceProduct->calculateTotalPrice();

        // Expected: (120 * 4) + 96 = 576
        $this->assertEquals(576.00, $result);
        $this->assertEquals(576.00, $invoiceProduct->total_price);
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        // Create without triggering auto-calculations by using make and setting calculated values directly
        $invoiceProduct = InvoiceProduct::factory()->make([
            'quantity' => '3.5',
            'price' => '99.99',
            'tax_rate' => '20.50',
            'is_custom_product' => '1'
        ]);
        
        // Manually set calculated values to avoid boot method interference
        $invoiceProduct->tax_amount = '71.74'; // 99.99 * 3.5 * 20.50 / 100 ≈ 71.74
        $invoiceProduct->total_price = '421.69'; // (99.99 * 3.5) + 71.74 ≈ 421.69
        
        // Save without triggering boot calculations by temporarily disabling events
        $invoiceProduct->saveQuietly();

        $this->assertIsFloat($invoiceProduct->quantity);
        $this->assertEquals(3.5, $invoiceProduct->quantity);
        
        $this->assertIsString($invoiceProduct->price); // Decimal cast returns string
        $this->assertEquals('99.99', $invoiceProduct->price);
        
        $this->assertIsString($invoiceProduct->tax_rate);
        $this->assertEquals('20.50', $invoiceProduct->tax_rate);
        
        $this->assertIsString($invoiceProduct->tax_amount);
        $this->assertEquals('71.74', $invoiceProduct->tax_amount);
        
        $this->assertIsString($invoiceProduct->total_price);
        $this->assertEquals('421.69', $invoiceProduct->total_price);
        
        $this->assertIsBool($invoiceProduct->is_custom_product);
        $this->assertTrue($invoiceProduct->is_custom_product);
    }

    #[Test]
    public function can_create_custom_product_without_product_relation(): void
    {
        $invoice = Invoice::factory()->create();
        
        $invoiceProduct = InvoiceProduct::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => null,
            'name' => 'Custom Product',
            'is_custom_product' => true,
            'price' => 150.00,
            'quantity' => 1,
            'tax_rate' => 0.00
        ]);

        $this->assertTrue($invoiceProduct->is_custom_product);
        $this->assertNull($invoiceProduct->product_id);
        $this->assertNull($invoiceProduct->product);
        $this->assertEquals('Custom Product', $invoiceProduct->name);
    }

    #[Test]
    public function handles_zero_tax_rate_correctly(): void
    {
        $invoiceProduct = InvoiceProduct::factory()->create([
            'price' => 100.00,
            'quantity' => 2,
            'tax_rate' => 0.00
        ]);

        $this->assertEquals(0.00, $invoiceProduct->tax_amount);
        $this->assertEquals(200.00, $invoiceProduct->total_price); // price * quantity + 0 tax
    }

    #[Test]
    public function handles_fractional_quantities(): void
    {
        $invoiceProduct = InvoiceProduct::factory()->create([
            'price' => 50.00,
            'quantity' => 2.5,
            'tax_rate' => 20.00
        ]);

        // Tax: 50 * 2.5 * 20 / 100 = 25
        $this->assertEquals(25.00, $invoiceProduct->tax_amount);
        
        // Total: (50 * 2.5) + 25 = 150
        $this->assertEquals(150.00, $invoiceProduct->total_price);
    }
}
