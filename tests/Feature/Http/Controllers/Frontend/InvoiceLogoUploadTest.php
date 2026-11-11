<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\User\Enums\Permission;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Permission as PermissionModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
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
        $permissionQuery = PermissionModel::where('name', Permission::INVOICE_CREATE->value);
        $permissionQuery->where('guard_name', 'web');
        $permission = $permissionQuery->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
        Storage::fake('public');

        [$this->business, $this->account] = $this->issuerOf($this->user);
        $this->actingAs($this->user, 'web');
    }

    #[Test]
    public function a_signed_in_invoice_uses_a_private_copy_of_the_selected_business_logo(): void
    {
        $number = $this->invoiceNumber();
        $disk = Storage::disk('public');
        $disk->put('parties/logo.png', 'business logo contents');
        $this->business->update(['logo' => 'parties/logo.png']);
        $url = route('frontend.invoice.store', ['locale' => 'cs']);
        $fileFactory = UploadedFile::fake();
        $response = $this->post(
            $url,
            $this->invoiceData(
                $number,
                [
                    'invoice_logo' => $fileFactory->image('unrelated.png'),
                ],
            ),
        );
        $response->assertRedirect();
        $invoiceQuery = Invoice::where('invoice_vs', $number);
        $invoice = $invoiceQuery->sole();
        $this->assertNotSame($this->business->logo, $invoice->supplier_logo);
        $this->assertSame(
            'business logo contents',
            $disk->get($invoice->supplier_logo),
        );
        $disk->delete($this->business->logo);
        $this->business->update(['logo' => null]);
        $httpResponse = $this->get(
            route('frontend.invoice.edit', ['locale' => 'cs', 'id' => $invoice->id]),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('wire:click="reloadBusinessData"', false);
        $copy = $invoice->supplier_logo;
        $httpResponse = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData($number),
        );
        $httpResponse->assertRedirect();
        $this->assertSame($copy, $invoice->fresh()->supplier_logo);
        $this->assertSame(
            'business logo contents',
            $disk->get($copy),
        );
        $httpResponse = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData($number, ['reload_business' => '1']),
        );
        $httpResponse->assertRedirect();
        $this->assertNull($invoice->fresh()->supplier_logo);
        $disk->assertMissing($copy);
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
        $httpResponse = $this->get(
            route('frontend.invoice.edit', ['locale' => 'cs', 'id' => $invoice->id]),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertDontSee('name="invoice_logo"', false);
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
