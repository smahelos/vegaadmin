<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PaymentMethod;
use App\Models\RoleLimit;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class InvoiceCrudControllerTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsInvoiceInput;
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
    }

    #[Test]
    public function an_invoice_an_admin_creates_counts_against_the_owners_quota(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $role = Role::findByName('frontend_user', 'web');
        $owner->assignRole($role);
        $roleLimitQuery = RoleLimit::where('role_id', $role->id);
        $roleLimitQuery->update(['invoices' => 1]);
        [$business, $account] = $this->issuerOf($owner);
        $data = $this->invoiceInput(
            $business,
            $account,
            ['user_id' => $owner->id],
        );
        $this->actingAs(
            $this->backendUser('can_create_invoice'),
            'backpack',
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertDatabaseHas('invoices', ['invoice_vs' => $data['invoice_vs']]);
        $blocked = $this->invoiceNumber();
        $httpResponse = $this->post(
            '/admin/invoice',
            array_replace($data, ['invoice_vs' => $blocked]),
        );
        $httpResponse = $httpResponse->assertSessionDoesntHaveErrors('invoice_vs');
        $httpResponse->assertRedirect();
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $blocked]);
    }

    #[Test]
    public function an_owner_without_a_plan_role_gets_no_invoice_whatever_the_admin_may_do(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        [$business, $account] = $this->issuerOf($owner);
        $data = $this->invoiceInput(
            $business,
            $account,
            ['user_id' => $owner->id],
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse->assertRedirect();
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $data['invoice_vs']]);
    }

    #[Test]
    public function create_and_edit_forms_use_the_document_parties(): void
    {
        $httpResponse = $this->get('/admin/invoice/create');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('name="business_id"', false);
        $httpResponse = $httpResponse->assertSee('name="customer_name"', false);
        $httpResponse = $httpResponse->assertDontSee('name="client_id"', false);
        $httpResponse->assertDontSee('forms/invoice.js', false);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create();
        $httpResponse = $this->get('/admin/invoice/' . $invoice->id . '/edit');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('name="bank_account_id"', false);
        $httpResponse->assertSee('name="customer_name"', false);
    }

    #[Test]
    public function a_new_contact_is_copied_and_the_typed_customer_is_ignored(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            ['user_id' => $this->regularUser->id, 'name' => 'Selected customer'],
        );
        $addressFactory = Address::factory();
        $address = $addressFactory->create(['business_id' => null, 'contact_id' => $contact->id]);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'contact_id' => $contact->id,
                'customer_name' => 'Ignored input',
                'customer_country' => 'invalid',
            ],
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_vs' => $data['invoice_vs'],
                'user_id' => $this->regularUser->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'supplier_name' => $business->name,
                'customer_name' => $contact->name,
                'customer_street' => $address->street,
                'account_number' => $account->number,
            ],
        );
    }

    #[Test]
    public function keeping_the_contact_saves_document_corrections_without_changing_the_contact(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->regularUser->id]);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'bank_account_id' => $account->id,
            ],
        );
        $httpResponse = $this->put(
            '/admin/invoice/' . $invoice->id,
            $this->invoiceInput(
                $business,
                $account,
                [
                    'user_id' => $this->regularUser->id,
                    'invoice_vs' => $invoice->invoice_vs,
                    'contact_id' => $contact->id,
                    'customer_name' => 'Corrected name',
                ],
            ),
        );
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertSame('Corrected name', $invoice->fresh()->customer_name);
        $this->assertSame($contact->name, $contact->fresh()->name);
    }

    #[Test]
    public function a_changed_contact_replaces_the_document_customer(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contactFactory = Contact::factory();
        $old = $contactFactory->create(['user_id' => $this->regularUser->id]);
        $new = $contactFactory->create(['user_id' => $this->regularUser->id]);
        $addressFactory = Address::factory();
        $addressFactory->create(['business_id' => null, 'contact_id' => $new->id]);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'business_id' => $business->id,
                'contact_id' => $old->id,
            ],
        );
        $httpResponse = $this->put(
            '/admin/invoice/' . $invoice->id,
            $this->invoiceInput(
                $business,
                $account,
                [
                    'user_id' => $this->regularUser->id,
                    'invoice_vs' => $invoice->invoice_vs,
                    'contact_id' => $new->id,
                    'customer_name' => 'Ignored name',
                ],
            ),
        );
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertSame($new->name, $invoice->fresh()->customer_name);
    }

    #[Test]
    public function selections_of_another_owner_are_rejected(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create();
        $httpResponse = $this->post(
            '/admin/invoice',
            $this->invoiceInput(
                $business,
                $account,
                [
                    'user_id' => $this->adminUser->id,
                    'contact_id' => $contact->id,
                ],
            ),
        );
        $httpResponse->assertSessionHasErrors(['business_id', 'contact_id']);
    }

    #[Test]
    public function a_cash_invoice_drops_the_account(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::CASH->value);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'payment_method_id' => $paymentMethodQuery->firstOrFail()->id,
            ],
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_vs' => $data['invoice_vs'],
                'bank_account_id' => null,
                'account_number' => null,
            ],
        );
    }

    #[Test]
    public function an_archived_contact_cannot_be_newly_selected(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            ['user_id' => $this->regularUser->id, 'archived_at' => now()],
        );
        $httpResponse = $this->post(
            '/admin/invoice',
            $this->invoiceInput(
                $business,
                $account,
                [
                    'user_id' => $this->regularUser->id,
                    'contact_id' => $contact->id,
                ],
            ),
        );
        $httpResponse->assertSessionHasErrors('contact_id');
    }

    #[Test]
    public function a_contact_without_a_complete_address_cannot_supply_a_new_snapshot(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->regularUser->id]);
        $httpResponse = $this->post(
            '/admin/invoice',
            $this->invoiceInput(
                $business,
                $account,
                [
                    'user_id' => $this->regularUser->id,
                    'contact_id' => $contact->id,
                ],
            ),
        );
        $httpResponse->assertSessionHasErrors('contact_id');
        $this->assertDatabaseMissing('invoices', ['contact_id' => $contact->id]);
    }

    #[Test]
    public function the_total_comes_from_the_lines_and_a_typed_amount_is_ignored(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '9999.99',
                'invoice-products' => json_encode(
                    [
                        $this->invoiceItem(
                            [
                                'quantity' => '2',
                                'price' => '100',
                                'tax_rate' => '21',
                            ],
                        ),
                        $this->invoiceItem(['price' => '0.10']),
                    ],
                ),
            ],
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $invoiceQuery = Invoice::where('invoice_vs', $data['invoice_vs']);
        $invoice = $invoiceQuery->sole();
        $this->assertSame('242.10', $invoice->payment_amount);
    }

    #[Test]
    public function an_invoice_needs_at_least_one_line(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'business_id' => $business->id,
            ],
        );
        $data = $this->invoiceInput(
            $business,
            $account,
            ['user_id' => $this->regularUser->id, 'invoice-products' => json_encode([])],
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse->assertSessionHasErrors('invoice-products');
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $data['invoice_vs']]);
        unset($data['invoice-products']);
        $httpResponse = $this->put('/admin/invoice/' . $invoice->id, $data);
        $httpResponse->assertSessionHasErrors('invoice-products');
    }

    #[Test]
    public function editing_an_invoice_replaces_its_fields_lines_and_total(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'business_id' => $business->id,
                'payment_currency' => 'CZK',
                'invoice_ks' => '0308',
                'template' => 'default',
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        $line = $invoiceProductFactory->create(['invoice_id' => $invoice->id, 'currency' => 'CZK']);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->regularUser->id,
                'invoice_vs' => $invoice->invoice_vs,
                'invoice_ks' => null,
                'status' => InvoiceStatus::PAID->value,
                'template' => InvoiceTemplate::MODERN->value,
                'invoice_text' => 'Paid in advance',
                'invoice-products' => json_encode(
                    [
                        $this->invoiceItem(
                            [
                                'name' => 'Design',
                                'quantity' => '3',
                                'price' => '33.33',
                            ],
                        ),
                    ],
                ),
            ],
        );
        $httpResponse = $this->put('/admin/invoice/' . $invoice->id, $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $invoice = $invoice->fresh();
        $this->assertSame('99.99', $invoice->payment_amount);
        $this->assertNull($invoice->invoice_ks);
        $this->assertSame('Paid in advance', $invoice->invoice_text);
        $this->assertSame('modern', $invoice->template);
        $this->assertSame(InvoiceStatus::PAID->value, $invoice->paymentStatus->slug);
        $this->assertDatabaseMissing('invoice_products', ['id' => $line->id]);
        $this->assertDatabaseHas(
            'invoice_products',
            [
                'invoice_id' => $invoice->id,
                'name' => 'Design',
                'total_price' => '99.99',
            ],
        );
    }

    #[Test]
    public function editing_an_invoice_identifies_an_unlabelled_bank_account_by_its_number(): void
    {
        [$business, $account] = $this->issuerOf($this->regularUser);
        $account->update(
            [
                'label' => null,
                'iban' => null,
                'number' => '2001234561',
            ],
        );
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'business_id' => $business->id,
                'bank_account_id' => $account->id,
            ],
        );
        $httpResponse = $this->get('/admin/invoice/' . $invoice->id . '/edit');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee(
            '<option value="' . $account->id . '" selected>2001234561</option>',
            false,
        );
    }

    #[Test]
    public function show_uses_the_saved_names(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            ['supplier_name' => 'Saved supplier', 'customer_name' => 'Saved customer'],
        );
        $httpResponse = $this->get('/admin/invoice/' . $invoice->id . '/show');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('Saved supplier');
        $httpResponse->assertSee('Saved customer');
    }

    #[Test]
    public function guests_and_admin_users_without_permission_cannot_write_invoices(): void
    {
        $auth = auth('backpack');
        $auth->logout();
        $httpResponse = $this->post('/admin/invoice', []);
        $httpResponse->assertRedirect(
            route('backpack.auth.login'),
        );
        $httpClient = $this->actingAs($this->regularUser, 'backpack');
        $httpResponse = $httpClient->post('/admin/invoice', []);
        $httpResponse->assertForbidden();
    }
}
