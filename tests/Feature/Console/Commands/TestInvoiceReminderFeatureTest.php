<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\StatusCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestInvoiceReminderFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Notification::fake();
        
        // Setup required data
        $this->setupStatusCategories();
    }

    private function setupStatusCategories(): void
    {
        // Create invoice payment status category
        $category = StatusCategory::firstOrCreate([
            'slug' => 'invoice-payment'
        ], [
            'name' => 'Invoice Payment Status',
            'description' => 'Payment status for invoices'
        ]);

        // Create required statuses
        Status::firstOrCreate([
            'slug' => 'unpaid',
            'category_id' => $category->id
        ], [
            'name' => 'Unpaid',
            'description' => 'Invoice is not paid yet'
        ]);
    }

    #[Test]
    public function command_executes_successfully(): void
    {
        $invoice = Invoice::factory()->create();
        
        $exitCode = Artisan::call('invoices:test-reminder', [
            'invoice_id' => $invoice->id
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_invoice_option(): void
    {
        $invoice = Invoice::factory()->create();
        
        $exitCode = Artisan::call('invoices:test-reminder', [
            'invoice_id' => $invoice->id
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_type_option(): void
    {
        $exitCode = Artisan::call('invoices:test-reminder', [
            '--type' => 'upcoming'
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_tests_different_reminder_types(): void
    {
        $types = ['upcoming', 'due', 'overdue'];
        
        foreach ($types as $type) {
            $exitCode = Artisan::call('invoices:test-reminder', [
                '--type' => $type
            ]);
            
            $this->assertEquals(0, $exitCode);
        }
    }

    #[Test]
    public function command_sends_test_reminder(): void
    {
        $client = Client::factory()->create([
            'email' => 'test@example.com'
        ]);
        
        $unpaidStatus = Status::where('slug', 'unpaid')->first();
        
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'payment_status_id' => $unpaidStatus->id
        ]);
        
        $exitCode = Artisan::call('invoices:test-reminder', [
            '--invoice' => $invoice->id,
            '--type' => 'due'
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('invoices:test-reminder');
        
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_handles_invalid_invoice(): void
    {
        $exitCode = Artisan::call('invoices:test-reminder', [
            '--invoice' => 999999
        ]);
        
        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    public function command_handles_invalid_type(): void
    {
        $exitCode = Artisan::call('invoices:test-reminder', [
            '--type' => 'invalid_type'
        ]);
        
        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    public function command_tests_upcoming_reminders(): void
    {
        $exitCode = Artisan::call('invoices:test-reminder', [
            '--type' => 'upcoming'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('upcoming', $output);
    }

    #[Test]
    public function command_tests_due_reminders(): void
    {
        $exitCode = Artisan::call('invoices:test-reminder', [
            '--type' => 'due'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('due', $output);
    }

    #[Test]
    public function command_tests_overdue_reminders(): void
    {
        $exitCode = Artisan::call('invoices:test-reminder', [
            '--type' => 'overdue'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('overdue', $output);
    }

    #[Test]
    public function command_validates_required_parameters(): void
    {
        // Command should work without parameters (using defaults)
        $exitCode = Artisan::call('invoices:test-reminder');
        
        $this->assertEquals(0, $exitCode);
    }
}
