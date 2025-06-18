<?php

namespace Tests\Feature\Services;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\Status;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceService();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function get_next_invoice_number_returns_first_number_for_new_user(): void
    {
        Auth::login($this->user);
        
        $nextNumber = $this->service->getNextInvoiceNumber();
        
        $this->assertNotEmpty($nextNumber);
        $this->assertTrue(str_contains($nextNumber, date('Y')));
    }

    #[Test]
    public function get_next_invoice_number_increments_existing_invoice(): void
    {
        Auth::login($this->user);
        
        // Create an existing invoice with a specific number
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'invoice_vs' => '20240001',
            'created_at' => now()->subDay()
        ]);
        
        $nextNumber = $this->service->getNextInvoiceNumber();
        
        // The regex matches all digits at the end, so '20240001' becomes '20240002'
        $this->assertEquals('20240002', $nextNumber);
    }

    #[Test]
    public function get_item_units_returns_translated_units(): void
    {
        $units = $this->service->getItemUnits();
        
        $this->assertIsArray($units);
        $this->assertArrayHasKey('hours', $units);
        $this->assertArrayHasKey('days', $units);
        $this->assertArrayHasKey('pieces', $units);
        $this->assertArrayHasKey('kilograms', $units);
        $this->assertArrayHasKey('grams', $units);
        $this->assertArrayHasKey('liters', $units);
        $this->assertArrayHasKey('meters', $units);
        $this->assertArrayHasKey('cubic_meters', $units);
        $this->assertArrayHasKey('centimeters', $units);
        $this->assertArrayHasKey('cubic_centimeters', $units);
        $this->assertArrayHasKey('milliliters', $units);
        
        // Verify all values are non-empty strings
        foreach ($units as $unit) {
            $this->assertIsString($unit);
            $this->assertNotEmpty($unit);
        }
    }

    #[Test]
    public function save_invoice_products_creates_custom_products(): void
    {
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        
        $products = [
            [
                'name' => 'Custom Product 1',
                'quantity' => 2,
                'price' => 100.50,
                'currency' => 'CZK',
                'unit' => 'ks',
                'tax_rate' => 21,
                'description' => 'Custom product description',
                'is_custom_product' => true
            ],
            [
                'name' => 'Custom Product 2',
                'quantity' => 1,
                'price' => 200,
                'currency' => 'EUR',
                'unit' => 'hours',
                'tax_rate' => 15,
                'category' => 'Service'
            ]
        ];
        
        $this->service->saveInvoiceProducts($invoice, $products);
        
        $this->assertDatabaseHas('invoice_products', [
            'invoice_id' => $invoice->id,
            'name' => 'Custom Product 1',
            'quantity' => 2,
            'price' => 100.50,
            'currency' => 'CZK',
            'unit' => 'ks',
            'tax_rate' => 21,
            'is_custom_product' => true,
            'product_id' => null
        ]);
        
        $this->assertDatabaseHas('invoice_products', [
            'invoice_id' => $invoice->id,
            'name' => 'Custom Product 2',
            'quantity' => 1,
            'price' => 200,
            'currency' => 'EUR',
            'unit' => 'hours',
            'tax_rate' => 15,
            'category' => 'Service',
            'product_id' => null
        ]);
        
        $this->assertEquals(2, InvoiceProduct::where('invoice_id', $invoice->id)->count());
    }

    #[Test]
    public function save_invoice_products_creates_products_with_product_id(): void
    {
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        $product = Product::factory()->create();
        
        $products = [
            [
                'product_id' => $product->id,
                'name' => 'Existing Product',
                'quantity' => 3,
                'price' => 50,
                'currency' => 'CZK',
                'unit' => 'pieces',
                'tax_rate' => 21
            ]
        ];
        
        $this->service->saveInvoiceProducts($invoice, $products);
        
        $this->assertDatabaseHas('invoice_products', [
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'name' => 'Existing Product',
            'quantity' => 3,
            'price' => 50,
            'is_custom_product' => false
        ]);
    }

    #[Test]
    public function save_invoice_products_calculates_tax_and_total_correctly(): void
    {
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        
        $products = [
            [
                'name' => 'Tax Test Product',
                'quantity' => 2,
                'price' => 100,
                'tax_rate' => 21,
                'currency' => 'CZK',
                'unit' => 'ks'
            ]
        ];
        
        $this->service->saveInvoiceProducts($invoice, $products);
        
        $invoiceProduct = InvoiceProduct::where('invoice_id', $invoice->id)->first();
        
        // Tax calculation: (2 * 100 * 21) / 100 = 42
        $this->assertEquals(42, $invoiceProduct->tax_amount);
        
        // Total calculation: 2 * 100 * (1 + 21/100) = 242
        $this->assertEquals(242, $invoiceProduct->total_price);
    }

    #[Test]
    public function store_temporary_invoice_creates_cache_entry(): void
    {
        $data = [
            'client_name' => 'Test Client',
            'amount' => 1000,
            'currency' => 'CZK'
        ];
        
        $token = $this->service->storeTemporaryInvoice($data);
        
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
        
        // Verify data is cached
        $cachedData = Cache::get('invoice_data_' . $token);
        $this->assertEquals($data, $cachedData);
    }

    #[Test]
    public function get_temporary_invoice_by_token_retrieves_cached_data(): void
    {
        $data = [
            'client_name' => 'Test Client',
            'amount' => 2000,
            'currency' => 'EUR'
        ];
        
        $token = $this->service->storeTemporaryInvoice($data);
        $retrievedData = $this->service->getTemporaryInvoiceByToken($token);
        
        $this->assertEquals($data, $retrievedData);
    }

    #[Test]
    public function get_temporary_invoice_by_token_returns_null_for_invalid_token(): void
    {
        $result = $this->service->getTemporaryInvoiceByToken('invalid_token');
        
        $this->assertNull($result);
    }

    #[Test]
    public function delete_temporary_invoice_removes_cache_entry(): void
    {
        $data = ['test' => 'data'];
        $token = $this->service->storeTemporaryInvoice($data);
        
        // Verify data exists
        $this->assertNotNull($this->service->getTemporaryInvoiceByToken($token));
        
        // Delete the entry
        $result = $this->service->deleteTemporaryInvoice($token);
        
        $this->assertTrue($result);
        
        // Verify data is gone
        $this->assertNull($this->service->getTemporaryInvoiceByToken($token));
    }

    #[Test]
    public function mark_invoice_as_paid_updates_status(): void
    {
        Auth::login($this->user);
        
        // Create paid status
        $paidStatus = Status::factory()->create(['slug' => 'paid']);
        
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_status_id' => 1 // Different status initially
        ]);
        
        $result = $this->service->markInvoiceAsPaid($invoice->id);
        
        $this->assertTrue($result);
        
        $invoice->refresh();
        $this->assertEquals($paidStatus->id, $invoice->payment_status_id);
    }

    #[Test]
    public function mark_invoice_as_paid_returns_false_when_no_paid_status_exists(): void
    {
        Auth::login($this->user);
        
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        
        $result = $this->service->markInvoiceAsPaid($invoice->id);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function mark_invoice_as_paid_returns_false_for_nonexistent_invoice(): void
    {
        Auth::login($this->user);
        
        $result = $this->service->markInvoiceAsPaid(99999);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function ensure_object_properties_sets_default_values(): void
    {
        $object = new \stdClass();
        $properties = ['due_in', 'payment_method_id', 'payment_status_id', 'payment_amount', 'custom_property'];
        
        $this->service->ensureObjectProperties($object, $properties);
        
        $this->assertEquals(14, $object->due_in);
        $this->assertEquals(1, $object->payment_method_id);
        $this->assertEquals(1, $object->payment_status_id);
        $this->assertEquals(0, $object->payment_amount);
        $this->assertEquals('', $object->custom_property);
    }

    #[Test]
    public function ensure_object_properties_converts_existing_values(): void
    {
        $object = new \stdClass();
        $object->due_in = '30';
        $object->payment_method_id = '5';
        $object->payment_status_id = '3';
        $object->payment_amount = '150.50';
        $object->custom_property = 'existing value';
        
        $properties = ['due_in', 'payment_method_id', 'payment_status_id', 'payment_amount', 'custom_property'];
        
        $this->service->ensureObjectProperties($object, $properties);
        
        $this->assertSame(30, $object->due_in);
        $this->assertSame(5, $object->payment_method_id);
        $this->assertSame(3, $object->payment_status_id);
        $this->assertSame(150.5, $object->payment_amount);
        $this->assertEquals('existing value', $object->custom_property);
    }
}
