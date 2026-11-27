<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\User\Enums\Permission;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
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
        $disk = Storage::disk('public');
        $disk->put('parties/logo.png', 'business logo contents');
        $this->business->update(['logo' => 'parties/logo.png']);
        $url = route('frontend.invoice.store', ['locale' => 'cs']);
        $fileFactory = UploadedFile::fake();
        $response = $this->post(
            $url,
            $this->invoiceData(
                [
                    'invoice_logo' => $fileFactory->image('unrelated.png'),
                ],
            ),
        );
        $response->assertRedirect();
        $invoice = $this->latestInvoice();
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
            $this->invoiceData(),
        );
        $httpResponse->assertRedirect();
        $this->assertSame($copy, $invoice->fresh()->supplier_logo);
        $this->assertSame(
            'business logo contents',
            $disk->get($copy),
        );
        $httpResponse = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData(['reload_business' => '1']),
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
        $draftFactory = $invoiceFactory->draft();
        $invoice = $draftFactory->create(
            ['user_id' => $this->user->id, 'business_id' => $this->business->id],
        );
        $httpResponse = $this->get(
            route('frontend.invoice.edit', ['locale' => 'cs', 'id' => $invoice->id]),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertDontSee('name="invoice_logo"', false);
    }

    #[Test]
    public function changing_the_business_replaces_the_copy_and_clears_it_when_the_business_has_no_logo(): void
    {
        $this->business->update(
            ['logo' => $this->sourceLogo('first.png')],
        );
        $httpResponse = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData(),
        );
        $httpResponse->assertSessionHasNoErrors();
        $invoice = $this->latestInvoice();
        $oldCopy = $invoice->supplier_logo;
        $this->assertNotNull($oldCopy);
        [$second, $secondAccount] = $this->issuerOf($this->user);

        $httpResponse = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData(
                ['business_id' => $second->id, 'bank_account_id' => $secondAccount->id],
            ),
        );

        $httpResponse->assertSessionHasNoErrors();
        $this->assertNull($invoice->fresh()->supplier_logo);
        $disk = Storage::disk('public');
        $disk->assertMissing($oldCopy);
        $disk->assertExists($this->business->logo);
    }

    #[Test]
    public function documents_with_the_same_source_have_independent_files(): void
    {
        $this->business->update(
            ['logo' => $this->sourceLogo('shared.png')],
        );
        $url = route('frontend.invoice.store', ['locale' => 'cs']);
        $httpResponse = $this->post($url, $this->invoiceData());
        $httpResponse->assertSessionHasNoErrors();
        $first = $this->latestInvoice();
        $httpResponse = $this->post($url, $this->invoiceData());
        $httpResponse->assertSessionHasNoErrors();
        $second = $this->latestInvoice();
        $this->assertNotSame($first->supplier_logo, $second->supplier_logo);
        [$withoutLogo, $otherAccount] = $this->issuerOf($this->user);

        $httpResponse = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $first->id]),
            $this->invoiceData(
                ['business_id' => $withoutLogo->id, 'bank_account_id' => $otherAccount->id],
            ),
        );

        $httpResponse->assertSessionHasNoErrors();
        $disk = Storage::disk('public');
        $disk->assertMissing($first->supplier_logo);
        $disk->assertExists($second->supplier_logo);
        $disk->assertExists($this->business->logo);
    }

    #[Test]
    public function a_failed_save_removes_its_new_logo_copy(): void
    {
        $this->business->update(
            ['logo' => $this->sourceLogo('business.png')],
        );
        // The typed customer is not registered under the contact's number, so the save fails after the copy
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);

        $httpResponse = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData(['contact_id' => $contact->id]),
        );

        $httpResponse->assertSessionHasErrors('customer_company_number');
        $disk = Storage::disk('public');
        $this->assertSame(
            [],
            $disk->allFiles('documents/logos'),
        );
        $disk->assertExists($this->business->logo);
        $invoiceQuery = Invoice::where('user_id', $this->user->id);
        $this->assertFalse($invoiceQuery->exists());
    }

    #[Test]
    public function a_failed_replacement_keeps_the_saved_logo_and_cleans_only_the_new_copy(): void
    {
        $this->business->update(
            ['logo' => $this->sourceLogo('before.png')],
        );
        $httpResponse = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $this->invoiceData(),
        );
        $httpResponse->assertSessionHasNoErrors();
        $invoice = $this->latestInvoice();
        [$nextBusiness, $nextAccount] = $this->issuerOf($this->user);
        $nextBusiness->update(
            ['logo' => $this->sourceLogo('after.png')],
        );
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);

        $httpResponse = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            $this->invoiceData(
                [
                    'business_id' => $nextBusiness->id,
                    'bank_account_id' => $nextAccount->id,
                    'contact_id' => $contact->id,
                ],
            ),
        );

        $httpResponse->assertSessionHasErrors('customer_company_number');
        $this->assertSame($invoice->supplier_logo, $invoice->fresh()->supplier_logo);
        $disk = Storage::disk('public');
        $this->assertSame(
            [$invoice->supplier_logo],
            $disk->allFiles('documents/logos'),
        );
        $disk->assertExists($nextBusiness->logo);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invoiceData(array $overrides = []): array
    {
        return $this->invoiceInput(
            $this->business,
            $this->account,
            $overrides,
        );
    }

    /** A draft has no number yet, so the stored invoice is found as the newest of the user. */
    private function latestInvoice(): Invoice
    {
        $invoiceQuery = Invoice::where('user_id', $this->user->id);
        $invoiceQuery->orderByDesc('id');

        return $invoiceQuery->firstOrFail();
    }

    private function sourceLogo(string $name): string
    {
        $path = 'sources/' . $name;
        $disk = Storage::disk('public');
        $disk->put($path, $name);

        return $path;
    }
}
