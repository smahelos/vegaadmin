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
 * An authenticated invoice takes the selected business logo and keeps its own file.
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
        $permissionQuery = Permission::where('name', 'frontend.can_create_edit_invoice');
        $permissionQuery->where('guard_name', 'web');
        $permission = $permissionQuery->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
        EntityLimit::create(
            [
                'permission_name' => 'frontend.can_create_edit_invoice',
                'entity_type' => 'invoice',
                'limit_value' => 10,
                'period_type' => 'monthly',
                'metric_type' => 'count',
                'description' => 'Invoice limit for frontend tests',
                'is_active' => true,
            ],
        );
        Storage::fake('public');

        [$this->business, $this->account] = $this->issuerOf($this->user);
        $this->actingAs($this->user, 'web');
    }

    #[Test]
    public function a_signed_in_invoice_uses_a_private_copy_of_the_selected_business_logo(): void
    {
        $disk = Storage::disk('public');
        $disk->put('parties/logo.png', 'business logo contents');
        $this->business->update(['logo' => 'parties/logo.png']);
        $response = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData(
                'TEST001',
                [
                    'invoice_logo' => UploadedFile::fake()->image('unrelated.png'),
                ],
            ),
        );
        $response->assertRedirect();
        $invoiceQuery = Invoice::where('invoice_vs', 'TEST001');
        $invoice = $invoiceQuery->sole();
        $this->assertNotSame($this->business->logo, $invoice->supplier_logo);
        $disk2 = Storage::disk('public');
        $this->assertSame(
            'business logo contents',
            $disk2->get($invoice->supplier_logo),
        );
        $disk3 = Storage::disk('public');
        $disk3->delete($this->business->logo);
        $this->business->update(['logo' => null]);
        $httpResponse = $this->get(
            route('frontend.invoice.edit', ['locale' => 'cs', 'id' => $invoice->id]),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('wire:click="reloadBusinessData"', false);
        $copy = $invoice->supplier_logo;
        $httpResponse2 = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData('TEST001'),
        );
        $httpResponse2->assertRedirect();
        $this->assertSame($copy, $invoice->fresh()->supplier_logo);
        $disk4 = Storage::disk('public');
        $this->assertSame(
            'business logo contents',
            $disk4->get($copy),
        );
        $httpResponse3 = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData('TEST001', ['reload_business' => '1']),
        );
        $httpResponse3->assertRedirect();
        $this->assertNull($invoice->fresh()->supplier_logo);
        $disk5 = Storage::disk('public');
        $disk5->assertMissing($copy);
    }

    #[Test]
    public function the_authenticated_forms_offer_the_business_logo_without_a_document_upload(): void
    {
        $httpResponse = $this->get(
            route('frontend.invoice.create', ['locale' => 'cs']),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertDontSee('name="invoice_logo"', false);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            ['user_id' => $this->user->id, 'business_id' => $this->business->id],
        );
        $httpResponse2 = $this->get(
            route('frontend.invoice.edit', ['locale' => 'cs', 'id' => $invoice->id]),
        );
        $httpResponse2 = $httpResponse2->assertOk();
        $httpResponse2->assertDontSee('name="invoice_logo"', false);
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
