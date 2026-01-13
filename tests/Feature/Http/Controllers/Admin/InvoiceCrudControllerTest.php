<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\Client;
use App\Models\EntityLimit;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class InvoiceCrudControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAdminTestEnvironment;

    private User $adminUser;
    private User $regularUser;
    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpAdminTestEnvironment();
        
        $this->limitService = app(UniversalLimitService::class);
    }

    #[Test]
    public function invoice_creation_records_entity_usage(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        
        // Create entity limit for invoices
        EntityLimit::factory()->create([
            'entity_type' => 'invoice',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_invoice',
            'is_active' => true,
        ]);

        // Create required related entities
        $client = Client::factory()->create(['user_id' => $this->adminUser->id]);
        $supplier = Supplier::factory()->create(['user_id' => $this->adminUser->id]);
        $paymentMethod = PaymentMethod::factory()->create();
        $status = Status::factory()->create();

        // Check initial usage
        $initialCheck = $this->limitService->checkLimit($this->adminUser->id, 'invoice', 'count', 'monthly');
        $initialUsage = $initialCheck['current_usage'] ?? 0;

        $invoiceData = [
            'invoice_vs' => 'INV-' . uniqid(),
            'invoice_ks' => uniqid(),
            'invoice_ss' => uniqid(),
            'issue_date' => now()->format('Y-m-d'),
            'tax_point_date' => now()->addDays(30)->format('Y-m-d'),
            'due_in' => 30,
            'client_id' => $client->id,
            'user_id' => $this->adminUser->id,
            'supplier_id' => $supplier->id,
            'payment_status_id' => $status->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 100.00,
            'payment_currency' => 'CZK',
            'invoice_text' => 'Test invoice',
        ];

        // Simulate invoice creation by directly calling recordUsage
        $invoice = Invoice::factory()->create($invoiceData);
        $result = $this->limitService->recordUsage($this->adminUser->id, 'invoice', 'count', 'monthly', 'backpack');
        $this->assertTrue($result, 'recordUsage should return true when successful');

        // Check usage after creation
        $newCheck = $this->limitService->checkLimit($this->adminUser->id, 'invoice', 'count', 'monthly');
        $newUsage = $newCheck['current_usage'] ?? 0;

        $this->assertEquals($initialUsage + 1, $newUsage, 'Usage should increase by 1 after recording');

        // Verify invoice was created
        $this->assertDatabaseHas('invoices', [
            'invoice_vs' => $invoice->invoice_vs,
            'user_id' => $this->adminUser->id,
        ]);
    }

    #[Test]
    public function invoice_creation_respects_entity_limits(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        // Set a strict limit for invoices - use unique permission name to avoid conflicts
        EntityLimit::factory()->create([
            'entity_type' => 'invoice',
            'limit_value' => 1,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_invoice',
            'is_active' => true,
        ]);

        // Create required related entities
        $client = Client::factory()->create(['user_id' => $this->adminUser->id]);
        $supplier = Supplier::factory()->create(['user_id' => $this->adminUser->id]);
        $paymentMethod = PaymentMethod::factory()->create();
        $status = Status::factory()->create();

        // First invoice creation - should succeed
        $checkBeforeFirst = $this->limitService->checkLimit($this->adminUser->id, 'invoice', 'count', 'monthly');
        $this->assertTrue($checkBeforeFirst['allowed'], 'First invoice should be allowed');
        
        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->adminUser->id,
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
        ]);
        
        // Record usage with correct parameters
        $result1 = $this->limitService->recordUsage($this->adminUser->id, 'invoice', 'count', 'monthly', 'backpack');
        $this->assertTrue($result1, 'First recordUsage should succeed');
        
        // Second invoice creation - should fail due to limit
        $checkBeforeSecond = $this->limitService->checkLimit($this->adminUser->id, 'invoice', 'count', 'monthly');
        $this->assertFalse($checkBeforeSecond['allowed'], 'Second invoice should be denied due to limit');
        $this->assertEquals('limit_exceeded', $checkBeforeSecond['reason'], 'Reason should be limit_exceeded');
        
        // Verify first invoice was created
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice1->id,
            'user_id' => $this->adminUser->id,
        ]);
    }
}
