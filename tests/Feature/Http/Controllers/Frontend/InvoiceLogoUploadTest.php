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
    public function a_signed_in_invoice_uses_a_private_copy_of_the_selected_business_logo(): void
    {
        Storage::disk('public')->put('parties/logo.png', 'business logo contents');
        $this->business->update(['logo' => 'parties/logo.png']);
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $this->invoiceData('TEST001', [
            'invoice_logo' => UploadedFile::fake()->image('unrelated.png'),
        ]));
        $response->assertRedirect();
        $invoice = Invoice::where('invoice_vs', 'TEST001')->sole();
        $this->assertNotSame($this->business->logo, $invoice->supplier_logo);
        $this->assertSame('business logo contents', Storage::disk('public')->get($invoice->supplier_logo));
        Storage::disk('public')->delete($this->business->logo);
        $this->business->update(['logo' => null]);
        $this->get(route('frontend.invoice.edit', ['locale' => 'cs', 'id' => $invoice->id]))
            ->assertOk()->assertSee('wire:click="reloadBusinessData"', false);
        $copy = $invoice->supplier_logo;
        $this->put(route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]), $this->invoiceData('TEST001'))
            ->assertRedirect();
        $this->assertSame($copy, $invoice->fresh()->supplier_logo);
        $this->assertSame('business logo contents', Storage::disk('public')->get($copy));
        $this->put(route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]), $this->invoiceData('TEST001', ['reload_business' => '1']))
            ->assertRedirect();
        $this->assertNull($invoice->fresh()->supplier_logo);
        Storage::disk('public')->assertMissing($copy);
    }

    #[Test]
    public function the_authenticated_forms_offer_the_business_logo_without_a_document_upload(): void
    {
        $this->get(route('frontend.invoice.create', ['locale' => 'cs']))->assertOk()->assertDontSee('name="invoice_logo"', false);
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id, 'business_id' => $this->business->id]);
        $this->get(route('frontend.invoice.edit', ['locale' => 'cs', 'id' => $invoice->id]))->assertOk()->assertDontSee('name="invoice_logo"', false);
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
