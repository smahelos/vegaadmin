<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\Client;
use App\Models\EntityLimit;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class InvoiceCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

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
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();

        // Check initial usage
        $initialCheck = $this->limitService->checkLimit(
            $this->adminUser->id,
            'invoice',
            'count',
            'monthly',
        );
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
        $result = $this->limitService->recordUsage(
            $this->adminUser->id,
            'invoice',
            'count',
            'monthly',
            'backpack',
        );
        $this->assertTrue($result, 'recordUsage should return true when successful');

        // Check usage after creation
        $newCheck = $this->limitService->checkLimit(
            $this->adminUser->id,
            'invoice',
            'count',
            'monthly',
        );
        $newUsage = $newCheck['current_usage'] ?? 0;

        $this->assertEquals(
            $initialUsage + 1,
            $newUsage,
            'Usage should increase by 1 after recording',
        );

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
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create();

        // First invoice creation - should succeed
        $checkBeforeFirst = $this->limitService->checkLimit(
            $this->adminUser->id,
            'invoice',
            'count',
            'monthly',
        );
        $this->assertTrue($checkBeforeFirst['allowed'], 'First invoice should be allowed');

        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->adminUser->id,
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
        ]);

        // Record usage with correct parameters
        $result1 = $this->limitService->recordUsage(
            $this->adminUser->id,
            'invoice',
            'count',
            'monthly',
            'backpack',
        );
        $this->assertTrue($result1, 'First recordUsage should succeed');

        // Second invoice creation - should fail due to limit
        $checkBeforeSecond = $this->limitService->checkLimit(
            $this->adminUser->id,
            'invoice',
            'count',
            'monthly',
        );
        $this->assertFalse($checkBeforeSecond['allowed'], 'Second invoice should be denied due to limit');
        $this->assertEquals(
            'limit_exceeded',
            $checkBeforeSecond['reason'],
            'Reason should be limit_exceeded',
        );

        // Verify first invoice was created
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice1->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    #[Test]
    public function store_assigns_the_admin_as_owner_when_none_is_sent(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $client = Client::factory()->create(['user_id' => $this->adminUser->id]);
        $supplier = Supplier::factory()->create(['user_id' => $this->adminUser->id]);
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $vs = (string) random_int(100000, 999999);

        $this->post('/admin/invoice', [
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'invoice_vs' => $vs,
            'due_in' => 14,
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 1000,
            'payment_status' => 'unpaid',
            'payment_currency' => 'CZK',
            'issue_date' => '2026-10-01',
            'tax_point_date' => '2026-10-01',
            'street' => 'Main 1',
            'city' => 'Praha',
            'zip' => '11000',
            'country' => 'CZ',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', ['invoice_vs' => $vs, 'user_id' => $this->adminUser->id]);
    }

    #[Test]
    public function update_keeps_the_owner_when_another_one_is_sent(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $client = Client::factory()->create(['user_id' => $this->adminUser->id]);
        $supplier = Supplier::factory()->create(['user_id' => $this->adminUser->id]);
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $invoice = Invoice::factory()->create([
            'user_id' => $this->adminUser->id,
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => Status::factory()->create()->id,
        ]);
        $otherUser = User::factory()->create();

        $this->put('/admin/invoice/' . $invoice->id, [
            'id' => $invoice->id,
            'user_id' => $otherUser->id,
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'invoice_vs' => $invoice->invoice_vs,
            'due_in' => 14,
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 1000,
            'payment_status' => 'unpaid',
            'payment_currency' => 'CZK',
            'issue_date' => '2026-10-01',
            'tax_point_date' => '2026-10-01',
            'street' => 'Main 1',
            'city' => 'Praha',
            'zip' => '11000',
            'country' => 'CZ',
            'invoice_text' => $text = 'Updated ' . uniqid(),
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'invoice_text' => $text,
            'user_id' => $this->adminUser->id,
        ]);
    }

    #[Test]
    public function show_displays_the_related_records_by_name(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $client = Client::factory()->create(['user_id' => $this->adminUser->id, 'name' => 'Client ' . uniqid()]);
        $supplier = Supplier::factory()->create(['user_id' => $this->adminUser->id, 'name' => 'Supplier ' . uniqid()]);
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = Status::factory()->create(['name' => 'Status ' . uniqid()]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->adminUser->id,
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
        ]);

        $response = $this->get('/admin/invoice/' . $invoice->id . '/show')->assertOk();

        $cells = $this->datagridCells($response->getContent());
        $this->assertSame($this->adminUser->name, $cells[__('invoices.fields.user_id')]);
        $this->assertSame($supplier->name, $cells[__('invoices.fields.supplier')]);
        $this->assertSame($client->name, $cells[__('invoices.fields.client')]);
        $this->assertSame($paymentMethod->name, $cells[__('invoices.fields.payment_method')]);
        $this->assertSame($status->name, $cells[__('invoices.fields.status')]);
    }

    /**
     * @return array<string, string> cell text keyed by the column label
     */
    private function datagridCells(string $html): array
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8">' . $html);
        $xpath = new DOMXPath($document);
        $cells = [];
        foreach ($xpath->query('//div[contains(@class, "bp-datagrid-item")]') as $item) {
            $title = $xpath->query('.//div[contains(@class, "bp-datagrid-title")]', $item)->item(0);
            $content = $xpath->query('.//div[contains(@class, "bp-datagrid-content")]', $item)->item(0);
            if ($title && $content) {
                $cells[trim($title->textContent)] = trim(preg_replace('/\s+/u', ' ', $content->textContent));
            }
        }

        return $cells;
    }
}
