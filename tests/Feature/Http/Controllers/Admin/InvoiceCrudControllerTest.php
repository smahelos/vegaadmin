<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PaymentMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class InvoiceCrudControllerTest extends TestCase
{
    use BuildsInvoiceInput;
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
    }

    #[Test]
    public function create_and_edit_forms_use_the_document_parties(): void
    {
        $this->get('/admin/invoice/create')->assertOk()
            ->assertSee('name="business_id"', false)->assertSee('name="customer_name"', false)
            ->assertDontSee('name="client_id"', false)->assertDontSee('forms/invoice.js', false);
        $invoice = Invoice::factory()->create();
        $this->get('/admin/invoice/' . $invoice->id . '/edit')->assertOk()
            ->assertSee('name="bank_account_id"', false)->assertSee('name="customer_name"', false);
    }

    #[Test]
    public function a_new_contact_is_copied_and_the_typed_customer_is_ignored(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contact = Contact::factory()->create(['user_id' => $this->regularUser->id, 'name' => 'Selected customer']);
        $address = Address::factory()->create(['business_id' => null, 'contact_id' => $contact->id]);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'contact_id' => $contact->id,
                'customer_name' => 'Ignored input',
                'customer_country' => 'invalid',
                'payment_amount' => '0',
            ],
        );
        $this->post('/admin/invoice', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'invoice_vs' => $data['invoice_vs'],
            'user_id' => $this->regularUser->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'supplier_name' => $business->name,
            'customer_name' => $contact->name,
            'customer_street' => $address->street,
            'account_number' => $account->number,
        ]);
    }

    #[Test]
    public function keeping_the_contact_saves_document_corrections_without_changing_the_contact(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contact = Contact::factory()->create(['user_id' => $this->regularUser->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->regularUser->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'bank_account_id' => $account->id,
        ]);
        $this->put('/admin/invoice/' . $invoice->id, $this->invoiceInput($business, $account, [
            'user_id' => $this->regularUser->id,
            'invoice_vs' => $invoice->invoice_vs,
            'contact_id' => $contact->id,
            'customer_name' => 'Corrected name',
            'payment_amount' => '0',
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Corrected name', $invoice->fresh()->customer_name);
        $this->assertSame($contact->name, $contact->fresh()->name);
    }

    #[Test]
    public function a_changed_contact_replaces_the_document_customer(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $old = Contact::factory()->create(['user_id' => $this->regularUser->id]);
        $new = Contact::factory()->create(['user_id' => $this->regularUser->id]);
        Address::factory()->create(['business_id' => null, 'contact_id' => $new->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->regularUser->id,
            'business_id' => $business->id,
            'contact_id' => $old->id,
        ]);
        $this->put('/admin/invoice/' . $invoice->id, $this->invoiceInput($business, $account, [
            'user_id' => $this->regularUser->id,
            'invoice_vs' => $invoice->invoice_vs,
            'contact_id' => $new->id,
            'customer_name' => 'Ignored name',
            'payment_amount' => '0',
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($new->name, $invoice->fresh()->customer_name);
    }

    #[Test]
    public function selections_of_another_owner_are_rejected(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contact = Contact::factory()->create();
        $this->post('/admin/invoice', $this->invoiceInput($business, $account, [
            'user_id' => $this->adminUser->id,
            'contact_id' => $contact->id,
            'payment_amount' => '0',
        ]))->assertSessionHasErrors(['business_id', 'contact_id']);
    }

    #[Test]
    public function a_cash_invoice_drops_the_account(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '0',
                'payment_method_id' => PaymentMethod::where('slug', PaymentMethodCode::CASH->value)->firstOrFail()->id,
            ],
        );
        $this->post('/admin/invoice', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'invoice_vs' => $data['invoice_vs'],
            'bank_account_id' => null,
            'account_number' => null,
        ]);
    }

    #[Test]
    public function an_archived_contact_cannot_be_newly_selected(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contact = Contact::factory()->create(['user_id' => $this->regularUser->id, 'archived_at' => now()]);
        $this->post('/admin/invoice', $this->invoiceInput($business, $account, [
            'user_id' => $this->regularUser->id,
            'contact_id' => $contact->id,
            'payment_amount' => '0',
        ]))->assertSessionHasErrors('contact_id');
    }

    #[Test]
    public function a_contact_without_a_complete_address_cannot_supply_a_new_snapshot(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contact = Contact::factory()->create(['user_id' => $this->regularUser->id]);
        $this->post('/admin/invoice', $this->invoiceInput($business, $account, [
            'user_id' => $this->regularUser->id,
            'contact_id' => $contact->id,
            'payment_amount' => '123.45',
        ]))->assertSessionHasErrors('contact_id');
        $this->assertDatabaseMissing('invoices', ['contact_id' => $contact->id]);
    }

    #[Test]
    public function a_document_without_lines_keeps_its_entered_amount_on_create_and_update(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '1234.56',
            ],
        );
        $this->post('/admin/invoice', $data)->assertSessionHasNoErrors()->assertRedirect();
        $invoice = Invoice::where('invoice_vs', $data['invoice_vs'])->sole();
        $this->assertSame('1234.56', $invoice->payment_amount);
        $this->put('/admin/invoice/' . $invoice->id, array_replace($data, ['payment_amount' => '2345.67']))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2345.67', $invoice->fresh()->payment_amount);
        $this->assertSame(0, $invoice->invoiceProducts()->count());
    }

    #[Test]
    public function editing_a_document_with_lines_keeps_the_lines_and_their_total(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->regularUser->id,
            'business_id' => $business->id,
            'payment_currency' => 'CZK',
        ]);
        $line = InvoiceProduct::factory()->create([
            'invoice_id' => $invoice->id,
            'currency' => 'CZK',
            'quantity' => '2',
            'price' => '100',
            'tax_rate' => '21',
            'tax_amount' => '42',
            'total_price' => '242',
        ]);
        $this->put('/admin/invoice/' . $invoice->id, $this->invoiceInput($business, $account, [
            'user_id' => $this->regularUser->id,
            'invoice_vs' => $invoice->invoice_vs,
            'payment_amount' => '9999.99',
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('242.00', $invoice->fresh()->payment_amount);
        $this->assertSame([$line->id], $invoice->invoiceProducts()->pluck('id')->all());
    }

    #[Test]
    public function editing_an_invoice_identifies_an_unlabelled_bank_account_by_its_number(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $account->update([
            'label' => null,
            'iban' => null,
            'number' => '2001234561',
        ]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->regularUser->id,
            'business_id' => $business->id,
            'bank_account_id' => $account->id,
        ]);
        $this->get('/admin/invoice/' . $invoice->id . '/edit')->assertOk()
            ->assertSee('<option value="' . $account->id . '" selected>2001234561</option>', false);
    }

    #[Test]
    public function show_uses_the_saved_names(): void
    {
        $invoice = Invoice::factory()->create(['supplier_name' => 'Saved supplier', 'customer_name' => 'Saved customer']);
        $this->get('/admin/invoice/' . $invoice->id . '/show')->assertOk()->assertSee('Saved supplier')->assertSee('Saved customer');
    }

    #[Test]
    public function guests_and_admin_users_without_permission_cannot_write_invoices(): void
    {
        auth('backpack')->logout();
        $this->post('/admin/invoice', [])->assertRedirect(route('backpack.auth.login'));
        $this->actingAs($this->regularUser, 'backpack')->post('/admin/invoice', [])->assertForbidden();
    }
}
