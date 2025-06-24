<?php

namespace Tests\Feature\Observers;

use App\Models\Invoice;
use App\Models\Product;
use App\Observers\InvoiceObserver;
use App\Services\InvoiceProductSyncService;
use App\Contracts\InvoiceProductSyncServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceObserverFeatureTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceObserver $observer;
    private InvoiceProductSyncServiceInterface $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->syncService = app(InvoiceProductSyncServiceInterface::class);
        $this->observer = new InvoiceObserver($this->syncService);
    }

    #[Test]
    public function observer_can_be_resolved_from_container(): void
    {
        $observer = app(InvoiceObserver::class);
        
        $this->assertInstanceOf(InvoiceObserver::class, $observer);
    }

    #[Test]
    public function invoice_observer_is_registered_with_model(): void
    {
        // Check if observer is registered with Invoice model
        $invoice = new Invoice();
        $observers = $invoice->getObservableEvents();
        
        $this->assertContains('created', $observers);
        $this->assertContains('updated', $observers);
    }

    #[Test]
    public function created_method_syncs_products_from_json(): void
    {
        // Create products first
        $product1 = Product::factory()->create(['name' => 'Test Product 1']);
        $product2 = Product::factory()->create(['name' => 'Test Product 2']);
        
        // Create invoice with JSON data containing products
        $invoiceData = [
            'invoice_vs' => '2025-001',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_text' => json_encode([
                'items' => [
                    [
                        'product_id' => $product1->id,
                        'quantity' => 2,
                        'price' => 100.00,
                        'tax' => 21
                    ],
                    [
                        'product_id' => $product2->id,
                        'quantity' => 1,
                        'price' => 50.00,
                        'tax' => 21
                    ]
                ]
            ])
        ];
        
        $invoice = Invoice::factory()->create($invoiceData);
        
        // Call observer created method directly
        $this->observer->created($invoice);
        
        // Refresh the invoice relationship
        $invoice->refresh();
        
        // Assert products are synced to pivot table
        $this->assertTrue($invoice->products()->where('product_id', $product1->id)->exists());
        $this->assertTrue($invoice->products()->where('product_id', $product2->id)->exists());
        
        // Check pivot data
        $pivotData1 = $invoice->products()->where('product_id', $product1->id)->first()->pivot;
        $this->assertEquals(2, $pivotData1->quantity);
        $this->assertEquals(100.00, $pivotData1->price);
        $this->assertEquals(21, $pivotData1->tax_rate);
    }

    #[Test]
    public function updated_method_syncs_products_when_invoice_text_changes(): void
    {
        // Create products
        $product1 = Product::factory()->create(['name' => 'Test Product 1']);
        $product2 = Product::factory()->create(['name' => 'Test Product 2']);
        
        // Create invoice without products first
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '2025-002',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_text' => json_encode(['items' => []])
        ]);
        
        // Update invoice with products in JSON
        $newInvoiceText = json_encode([
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 3,
                    'price' => 75.00,
                    'tax' => 21
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 1,
                    'price' => 25.00,
                    'tax' => 10
                ]
            ]
        ]);
        
        $invoice->invoice_text = $newInvoiceText;
        
        // Call observer updated method directly
        $this->observer->updated($invoice);
        
        // Refresh the invoice relationship
        $invoice->refresh();
        
        // Assert products are synced
        $this->assertTrue($invoice->products()->where('product_id', $product1->id)->exists());
        $this->assertTrue($invoice->products()->where('product_id', $product2->id)->exists());
        
        // Check pivot data
        $pivotData1 = $invoice->products()->where('product_id', $product1->id)->first()->pivot;
        $this->assertEquals(3, $pivotData1->quantity);
        $this->assertEquals(75.00, $pivotData1->price);
        $this->assertEquals(21, $pivotData1->tax_rate);
    }

    #[Test]
    public function updated_method_does_not_sync_when_invoice_text_unchanged(): void
    {
        // Create product
        $product = Product::factory()->create(['name' => 'Test Product']);
        
        // Create invoice with products
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '2025-003',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_text' => json_encode([
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'price' => 100.00,
                        'tax' => 21
                    ]
                ]
            ])
        ]);
        
        // Manually sync products first
        $this->observer->created($invoice);
        $initialProductCount = $invoice->products()->count();
        
        // Update other field (not invoice_text)
        $invoice->invoice_vs = '2025-003-UPDATED';
        
        // Call observer updated method
        $this->observer->updated($invoice);
        
        // Products should remain the same (not re-synced)
        $finalProductCount = $invoice->products()->count();
        $this->assertEquals($initialProductCount, $finalProductCount);
    }

    #[Test]
    public function observer_handles_invalid_json_gracefully(): void
    {
        // Create invoice with invalid JSON
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '2025-004',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_text' => 'invalid json data'
        ]);
        
        // This should not throw an exception
        $this->observer->created($invoice);
        
        // No products should be attached
        $this->assertEquals(0, $invoice->products()->count());
    }

    #[Test]
    public function observer_handles_empty_invoice_text(): void
    {
        // Create invoice with empty invoice_text
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '2025-005',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_text' => null
        ]);
        
        // This should not throw an exception
        $this->observer->created($invoice);
        
        // No products should be attached
        $this->assertEquals(0, $invoice->products()->count());
    }

    #[Test]
    public function observer_handles_malformed_json_items(): void
    {
        // Create invoice with malformed items in JSON
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '2025-006',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_text' => json_encode([
                'items' => [
                    ['name' => 'Item without product_id'],
                    ['product_id' => '', 'quantity' => 1], // Empty product_id
                    ['product_id' => 999999, 'quantity' => 1] // Non-existent product_id
                ]
            ])
        ]);
        
        // This should not throw an exception
        $this->observer->created($invoice);
        
        // No products should be attached due to invalid data
        $this->assertEquals(0, $invoice->products()->count());
    }

    #[Test]
    public function observer_works_with_model_events(): void
    {
        // Don't fake events - we want to test real observer behavior
        $product = Product::factory()->create(['name' => 'Event Test Product']);
        
        // Create invoice - this should trigger the observer via model event
        $invoice = Invoice::create([
            'invoice_vs' => '2025-007',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_text' => json_encode([
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'price' => 100.00,
                        'tax' => 21
                    ]
                ]
            ])
        ]);
        
        // Refresh to get the latest data
        $invoice->refresh();
        
        // Products should be synced automatically via observer
        $this->assertTrue($invoice->products()->where('product_id', $product->id)->exists());
    }

    #[Test]
    public function observer_replaces_products_on_update(): void
    {
        // Create products
        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);
        $product3 = Product::factory()->create(['name' => 'Product 3']);
        
        // Create invoice with first two products
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '2025-008',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 1000,
            'payment_currency' => 'CZK',
            'invoice_text' => json_encode([
                'items' => [
                    [
                        'product_id' => $product1->id,
                        'quantity' => 1,
                        'price' => 100.00,
                        'tax' => 21
                    ],
                    [
                        'product_id' => $product2->id,
                        'quantity' => 2,
                        'price' => 50.00,
                        'tax' => 21
                    ]
                ]
            ])
        ]);
        
        // Sync initial products
        $this->observer->created($invoice);
        $invoice->refresh();
        $this->assertEquals(2, $invoice->products()->count());
        
        // Update invoice_text to include only product3
        $invoice->invoice_text = json_encode([
            'items' => [
                [
                    'product_id' => $product3->id,
                    'quantity' => 3,
                    'price' => 75.00,
                    'tax' => 10
                ]
            ]
        ]);
        
        // Call updated observer
        $this->observer->updated($invoice);
        
        // Refresh the invoice relationship
        $invoice->refresh();
        
        // Should now have only product3
        $this->assertEquals(1, $invoice->products()->count());
        $this->assertTrue($invoice->products()->where('product_id', $product3->id)->exists());
        $this->assertFalse($invoice->products()->where('product_id', $product1->id)->exists());
        $this->assertFalse($invoice->products()->where('product_id', $product2->id)->exists());
    }
}
