<?php

namespace Tests\Feature\Infrastructure\Invoice;

use App\Application\Product\Contracts\InvoiceProductWriteRepositoryInterface;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentInvoiceProductRepositoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceProductWriteRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
    // Use application write repository bound to EloquentInvoiceProductRepository
    $this->repo = app(InvoiceProductWriteRepositoryInterface::class);
    }

    #[Test]
    public function bulk_create_persists_products(): void
    {
        $invoice = Invoice::factory()->create();
        $product = Product::factory()->create(['price' => 100]);
        $data = [
            [
                'product_id' => $product->id,
                'title' => $product->name,
                'quantity' => 2,
                'unit' => 'pieces',
                'price' => 100,
                'tax_rate' => 21,
                'tax_amount' => 42,
                'total_price' => 242,
            ],
        ];
        $this->repo->bulkCreate($invoice->id, $data);
        $this->assertCount(1, $invoice->invoiceProducts()->get());
        $saved = $invoice->invoiceProducts()->first();
        $this->assertEquals(2, $saved->quantity);
        $this->assertEquals(242.0, (float)$saved->total_price);
    }
}
