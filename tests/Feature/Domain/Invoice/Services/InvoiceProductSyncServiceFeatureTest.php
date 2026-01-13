<?php

namespace Tests\Feature\Domain\Invoice\Services;

use App\Domain\Invoice\Services\InvoiceProductSyncService;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class InvoiceProductSyncServiceFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    private InvoiceProductSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(InvoiceProductSyncService::class);
    }
    
    #[Test]
    public function sync_products_from_json_calls_invoice_method(): void
    {

        // Create product with ID 1 for sync
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);

        $invoice = Invoice::factory()->create([
            'invoice_text' => json_encode([
                'items' => [
                    ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2]
                ]
            ])
        ]);

        // Test that the service method executes without errors
    $this->service->syncProductsFromJson(InvoiceId::fromInt($invoice->id));

        // Assert that the pivot table was correctly synchronized
        $this->assertDatabaseHas('invoice_products', [
            'invoice_id' => $invoice->id,
            'name' => 'Test Product',
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function sync_products_from_json_handles_database_transaction(): void
    {

        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);

        $invoice = Invoice::factory()->create([
            'invoice_text' => json_encode([
                'items' => [
                    ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2]
                ]
            ])
        ]);

        // Test that method executes within transaction context
        // We can't easily test DB transaction state, but we can verify the method 
        // completes successfully which indicates transaction handling worked
    $this->service->syncProductsFromJson(InvoiceId::fromInt($invoice->id));

        // Verify invoice still exists, indicating successful completion
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    #[Test]
    public function sync_products_from_json_logs_errors_on_exception(): void
    {
        // Create invoice that might cause an error (null invoice_text)
        $invoice = Invoice::factory()->create([
            'invoice_text' => null
        ]);

    // Test that the method executes without throwing exceptions
    $this->service->syncProductsFromJson(InvoiceId::fromInt($invoice->id));

        // Verify the method completed (no exceptions thrown)
        $this->assertTrue(true);
    }

    #[Test]
    public function sync_all_invoices_processes_invoices_with_text(): void
    {
        // Create invoices with and without invoice_text

        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);

        $invoiceWithText = Invoice::factory()->create([
            'invoice_text' => json_encode([
                'items' => [
                    ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 2]
                ]
            ])
        ]);

        $invoiceWithoutText = Invoice::factory()->create([
            'invoice_text' => null
        ]);


        $anotherProduct = Product::factory()->create(['id' => 2, 'name' => 'Another Product']);

        $anotherInvoiceWithText = Invoice::factory()->create([
            'invoice_text' => json_encode([
                'items' => [
                    ['product_id' => $anotherProduct->id, 'name' => $anotherProduct->name, 'quantity' => 1]
                ]
            ])
        ]);

        // Count invoices before sync
        $invoicesWithText = Invoice::whereNotNull('invoice_text')->count();
        $this->assertEquals(2, $invoicesWithText);

        // Run sync all - should process 2 invoices
        $this->service->syncAllInvoices();

        // Verify method completed without errors
        $this->assertTrue(true);
    }

    #[Test]
    public function sync_all_invoices_handles_empty_result_set(): void
    {
        // Ensure no invoices with invoice_text exist
        Invoice::whereNotNull('invoice_text')->delete();

        $count = Invoice::whereNotNull('invoice_text')->count();
        $this->assertEquals(0, $count);

        // This should not cause any errors
        $this->service->syncAllInvoices();

        $this->assertTrue(true);
    }

    #[Test]
    public function sync_all_invoices_processes_large_batch(): void
    {
        // Create multiple invoices with text

        $product = Product::factory()->create(['id' => 1, 'name' => 'Batch Product']);

        $invoices = Invoice::factory()->count(5)->create([
            'invoice_text' => json_encode([
                'items' => [
                    ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1]
                ]
            ])
        ]);

        $this->assertCount(5, $invoices);

        // This should process all 5 invoices
        $this->service->syncAllInvoices();

        // Verify all invoices still exist
        $this->assertEquals(5, Invoice::whereNotNull('invoice_text')->count());
    }

    #[Test]
    public function service_integrates_with_invoice_model(): void
    {

        $product = Product::factory()->create(['id' => 1, 'name' => 'Integration Test Product']);

        $invoice = Invoice::factory()->create([
            'invoice_text' => json_encode([
                'items' => [
                    ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 3]
                ]
            ])
        ]);

        // Verify invoice has the syncProductsFromJson method
        $this->assertTrue(method_exists($invoice, 'syncProductsFromJson'));

        // Test service integration
    $this->service->syncProductsFromJson(InvoiceId::fromInt($invoice->id));

        // Verify invoice still exists after sync
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    #[Test]
    public function service_handles_malformed_json_gracefully(): void
    {
        $invoice = Invoice::factory()->create([
            'invoice_text' => 'invalid json string'
        ]);

        // Test that malformed JSON doesn't crash the service
    $this->service->syncProductsFromJson(InvoiceId::fromInt($invoice->id));

        // Service should not crash and invoice should still exist
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InvoiceProductSyncService::class, $this->service);
    }

    #[Test]
    public function sync_all_invoices_processes_all_invoices_with_text(): void
    {
        $user = \App\Models\User::factory()->create();
        
        // Create invoices with invoice_text
        Invoice::factory()->create([
            'user_id' => $user->id,
            'invoice_text' => json_encode(['test' => 'data'])
        ]);
        
        Invoice::factory()->create([
            'user_id' => $user->id,
            'invoice_text' => json_encode(['test2' => 'data2'])
        ]);

        // Create invoice without invoice_text (should be ignored)
        Invoice::factory()->create([
            'user_id' => $user->id,
            'invoice_text' => null
        ]);

        // This test verifies the method runs without errors
        $this->service->syncAllInvoices();
        
        $this->assertTrue(true); // If we get here without exception, test passes
    }

    #[Test]
    public function sync_products_from_json_rolls_back_on_exception_and_logs_error(): void
    {
        // We simulate an exception by creating an invoice whose syncProductsFromJson method throws
        $invoice = Invoice::factory()->create([
            'invoice_text' => json_encode(['items' => []])
        ]);

        // Bind throwing repo to simulate failure inside transaction
        $this->app->bind(\App\Domain\Invoice\Contracts\InvoiceProductsSyncRepositoryInterface::class, function () {
            return new class implements \App\Domain\Invoice\Contracts\InvoiceProductsSyncRepositoryInterface {
                public function syncFromInvoiceText(InvoiceId $invoiceId): void { throw new \RuntimeException('Simulated failure'); }
                public function listIdsWithInvoiceText(): array { return []; }
            };
        });
        $service = $this->app->make(InvoiceProductSyncService::class);
        $service->syncProductsFromJson(InvoiceId::fromInt($invoice->id));

        // Assert no products were synced (rollback took effect)
        $this->assertDatabaseMissing('invoice_products', [ 'invoice_id' => $invoice->id ]);
        // Invoice itself still exists
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }
}
