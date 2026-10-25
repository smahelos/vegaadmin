<?php

namespace Tests\Feature\Models;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceProductFeatureTest extends TestCase
{
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
            'tax_rate' => 21.00,
        ]);

        $this->assertDatabaseHas('invoice_products', [
            'id' => $invoiceProduct->id,
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'name' => 'Test Product',
            'quantity' => 2,
            'price' => 100.00,
            'tax_rate' => 21.00,
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
            'tax_rate' => 0.00,
        ]);

        $this->assertTrue($invoiceProduct->is_custom_product);
        $this->assertNull($invoiceProduct->product_id);
        $this->assertNull($invoiceProduct->product);
        $this->assertEquals('Custom Product', $invoiceProduct->name);
    }

    #[Test]
    public function stores_a_fractional_quantity(): void
    {
        $invoiceProduct = InvoiceProduct::factory()->create(['quantity' => '1.5']);

        $this->assertSame('1.500', $invoiceProduct->fresh()->quantity);
    }
}
