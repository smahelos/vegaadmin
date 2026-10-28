<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\EntityLimit;
use App\Models\Invoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

/**
 * The logo uploaded with an invoice is validated and stored on the public disk.
 */
final class InvoiceLogoUploadTest extends TestCase
{
    use BuildsInvoiceInput, CreatesFrontendTestEnvironment;

    private Business $business;

    private BankAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();
        $permission = Permission::where('name', 'frontend.can_create_edit_invoice')
            ->where('guard_name', 'web')
            ->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
        EntityLimit::create([
            'permission_name' => 'frontend.can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'description' => 'Invoice limit for frontend tests',
            'is_active' => true,
        ]);
        Storage::fake('public');

        [$this->business, $this->account] = $this->issuerOf($this->user);
        $this->actingAs($this->user, 'web');
    }

    #[Test]
    public function invoice_can_be_created_with_logo_upload(): void
    {
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $this->invoiceData('TEST001', [
            'invoice_logo' => UploadedFile::fake()->image('invoice_logo.png', 100, 100)->size(1000),
        ]));

        $response->assertRedirect(route('frontend.invoices', ['locale' => 'cs']));
        $invoice = Invoice::where('invoice_vs', 'TEST001')->firstOrFail();
        $this->assertSame($this->business->id, $invoice->business_id);
        $this->assertStringContainsString('invoices/logos', (string) $invoice->invoice_logo);
        $this->assertTrue(Storage::disk('public')->exists((string) $invoice->invoice_logo));
    }

    #[Test]
    public function invoice_creation_fails_with_invalid_logo_format(): void
    {
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $this->invoiceData('TEST002', [
            'invoice_logo' => UploadedFile::fake()->create('document.pdf', 1000),
        ]));

        $response->assertSessionHasErrors('invoice_logo');
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => 'TEST002']);
    }

    #[Test]
    public function invoice_creation_fails_with_oversized_logo(): void
    {
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $this->invoiceData('TEST003', [
            'invoice_logo' => UploadedFile::fake()->image('large_logo.png', 100, 100)->size(3000),
        ]));

        $response->assertSessionHasErrors('invoice_logo');
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => 'TEST003']);
    }

    #[Test]
    public function invoice_can_be_updated_with_logo_upload(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
        ]);

        $response = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData($invoice->invoice_vs, [
                'invoice_logo' => UploadedFile::fake()->image('updated_logo.png', 100, 100)->size(800),
            ]),
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertStringContainsString('invoices/logos', (string) $invoice->invoice_logo);
        $this->assertTrue(Storage::disk('public')->exists((string) $invoice->invoice_logo));
    }

    #[Test]
    public function invoice_can_be_created_without_logo(): void
    {
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $this->invoiceData('TEST004'));

        $response->assertRedirect(route('frontend.invoices', ['locale' => 'cs']));
        $this->assertNull(Invoice::where('invoice_vs', 'TEST004')->firstOrFail()->invoice_logo);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invoiceData(string $number, array $overrides = []): array
    {
        return $this->invoiceInput(
            $this->business,
            $this->account,
            ['invoice_vs' => $number, ...$overrides],
        );
    }
}
