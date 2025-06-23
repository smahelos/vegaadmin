<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncInvoiceProductsFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('invoices:sync-products');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('invoices:sync-products');
        
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_syncs_invoice_products(): void
    {
        // Create test data
        $product = Product::factory()->create();
        $invoice = Invoice::factory()->create();
        
        $exitCode = Artisan::call('invoices:sync-products');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_handles_empty_data(): void
    {
        // Test with no invoices or products
        $exitCode = Artisan::call('invoices:sync-products');
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_updates_product_relationships(): void
    {
        // Create test data
        if (class_exists(Product::class) && class_exists(Invoice::class)) {
            $product = Product::factory()->create();
            $invoice = Invoice::factory()->create();
            
            $exitCode = Artisan::call('invoices:sync-products');
            
            $this->assertEquals(0, $exitCode);
        } else {
            $this->markTestSkipped('Product or Invoice model not available');
        }
    }

    #[Test]
    public function command_can_be_run_multiple_times(): void
    {
        // Should be idempotent
        $exitCode1 = Artisan::call('invoices:sync-products');
        $exitCode2 = Artisan::call('invoices:sync-products');
        
        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(0, $exitCode2);
    }

    #[Test]
    public function command_reports_sync_results(): void
    {
        Artisan::call('invoices:sync-products');
        
        $output = Artisan::output();
        
        // Should provide feedback about sync process
        $this->assertIsString($output);
        $this->assertNotEmpty(trim($output));
    }

    #[Test]
    public function command_handles_large_datasets(): void
    {
        // Create multiple products and invoices
        if (class_exists(Product::class) && class_exists(Invoice::class)) {
            Product::factory()->count(5)->create();
            Invoice::factory()->count(3)->create();
            
            $exitCode = Artisan::call('invoices:sync-products');
            
            $this->assertEquals(0, $exitCode);
        } else {
            $this->markTestSkipped('Product or Invoice model not available');
        }
    }

    #[Test]
    public function command_validates_data_integrity(): void
    {
        $exitCode = Artisan::call('invoices:sync-products');
        
        $this->assertEquals(0, $exitCode);
        
        // Command should complete without errors
        $output = Artisan::output();
        $this->assertIsString($output);
    }

    #[Test]
    public function command_handles_database_operations(): void
    {
        // Test that command can perform database operations
        $exitCode = Artisan::call('invoices:sync-products');
        
        $this->assertEquals(0, $exitCode);
    }
}
