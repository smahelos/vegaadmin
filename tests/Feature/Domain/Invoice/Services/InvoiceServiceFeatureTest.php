<?php

namespace Tests\Feature\Domain\Invoice\Services;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Models\Invoice;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

/**
 * Consolidated feature tests for InvoiceService.
 * Original file renamed to match *FeatureTest naming convention.
 */
class InvoiceServiceFeatureTest extends TestCase
{
    use CreatesFrontendTestEnvironment;

    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        // Resolve through container (constructor now expects repository dependencies)
        $this->service = app(InvoiceService::class);
    }

    // --- Save products ---

    #[Test]
    public function save_invoice_products_stores_each_line_with_its_computed_amounts(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $this->user->id]);
        $productFactory = Product::factory();
        $product = $productFactory->create(['user_id' => $this->user->id]);

        $this->service->saveInvoiceProducts(
            InvoiceId::fromInt($invoice->id),
            [
                new InvoiceProductWriteData(
                    name: 'Consulting',
                    quantity: '1.5',
                    unit: Unit::HOURS,
                    price: Money::fromString('249.30', 'CZK'),
                    taxRate: '12',
                    category: 'Service',
                ),
                new InvoiceProductWriteData(
                    name: 'Existing Product',
                    quantity: '3',
                    unit: Unit::PIECES,
                    price: Money::fromString('74.67', 'CZK'),
                    taxRate: '21',
                    productId: ProductId::fromInt($product->id),
                ),
            ],
        );

        $this->assertDatabaseHas(
            'invoice_products',
            [
                'invoice_id' => $invoice->id,
                'name' => 'Consulting',
                'product_id' => null,
                'is_custom_product' => true,
                'quantity' => '1.500',
                'unit' => 'hours',
                'price' => '249.30',
                'tax_rate' => '12.00',
                'tax_amount' => '44.87',
                'total_price' => '418.82',
                'category' => 'Service',
            ],
        );
        $this->assertDatabaseHas(
            'invoice_products',
            [
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'is_custom_product' => false,
                'tax_amount' => '47.04',
                'total_price' => '271.05',
            ],
        );
    }

    // --- Mark as paid ---

    // --- Ensure object properties ---

    // --- Template handling ---
}
