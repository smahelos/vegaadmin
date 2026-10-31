<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Http\Requests\InvoiceRequest;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\EntityLimit;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Status;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

/**
 * Validation of the invoice form: a signed-in user selects a business, its account and a contact of their own,
 * a guest enters both parties and the account.
 */
final class InvoiceRequestFeatureTest extends TestCase
{
    use BuildsInvoiceInput, CreatesFrontendTestEnvironment;

    private Business $business;

    private BankAccount $account;

    /** @var array<string, mixed> */
    private array $validInvoiceData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();
        foreach (['12.00', '21.00'] as $rate) {
            Tax::factory()->create([
                'name' => 'DPH ' . $rate . ' ' . uniqid(),
                'slug' => 'dph',
                'rate' => $rate,
            ]);
        }

        [$this->business, $this->account] = $this->issuerOf($this->user);
        $this->validInvoiceData = $this->invoiceInput(
            $this->business,
            $this->account,
            [
                'invoice_ks' => '0308',
                'invoice_ss' => '1234',
                'invoice_text' => 'Thank you for your order.',
                'customer_email' => 'ucetni@odberatel.test',
                'customer_phone' => '+420 700 111 222',
                'invoice-products' => [$this->item()],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, string>
     */
    private function item(array $overrides = []): array
    {
        return $this->invoiceItem($overrides + [
            'quantity' => '1.5',
            'price' => '1200.50',
            'tax_rate' => '21.00',
        ]);
    }

    /**
     * The validator InvoiceRequest builds for the data; the party rules read the request input and the user.
     *
     * @param  array<string, mixed>  $data
     */
    private function validator(
        array $data,
        ?User $user = null,
        bool $guest = false,
    ): ValidatorInstance {
        $request = InvoiceRequest::create(
            '/',
            'POST',
            $data,
        );
        $request->setUserResolver(fn (): ?User => $guest ? null : ($user ?? $this->user));
        if ($guest) {
            $request->setRouteResolver(fn (): object => (object) ['name' => 'frontend.invoice.store.guest']);
        }

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages(),
            $request->attributes(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, list<string>>
     */
    private function errors(
        array $data,
        ?User $user = null,
        bool $guest = false,
    ): array {
        return $this->validator(
            $data,
            $user,
            $guest,
        )->errors()->toArray();
    }

    /**
     * Errors of the given item lines, validated as InvoiceRequest validates them.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array<string, list<string>>
     */
    private function itemErrors(
        array $items,
        ?User $user = null,
        string $currency = 'CZK',
    ): array {
        if ($user !== null) {
            [$business, $account] = $this->issuerOf($user);
            $data = $this->invoiceInput(
                $business,
                $account,
                ['invoice-products' => $items, 'payment_currency' => $currency],
            );
        } else {
            $data = ['invoice-products' => $items, 'payment_currency' => $currency] + $this->validInvoiceData;
        }

        return $this->errors($data, $user);
    }

    #[Test]
    public function validation_passes_with_a_business_its_account_and_a_customer(): void
    {
        $this->assertSame([], $this->errors($this->validInvoiceData));
    }

    #[Test]
    public function validation_passes_with_a_contact_of_the_user(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);

        $this->assertSame([], $this->errors(['contact_id' => $contact->id] + $this->validInvoiceData));
    }

    #[Test]
    public function a_guest_invoice_passes_with_both_parties_and_an_account(): void
    {
        $data = $this->guestInvoiceInput([
            'payment_currency' => 'EUR',
            'supplier_registration_country' => 'CZ',
            'supplier_company_number' => '27082440',
            'customer_vat_number' => 'CZ699001182',
            'invoice-products' => [$this->item(['currency' => 'EUR'])],
        ]);

        $this->assertSame([], $this->errors($data, guest: true));
    }

    #[Test]
    public function a_guest_cannot_refer_to_stored_parties(): void
    {
        $data = $this->guestInvoiceInput([
            'business_id' => $this->business->id,
            'bank_account_id' => $this->account->id,
            'contact_id' => Contact::factory()->create(['user_id' => $this->user->id])->id,
            'save_customer_to_contacts' => '1',
            'invoice-products' => [$this->item()],
        ]);

        $this->assertSame([
            'business_id',
            'bank_account_id',
            'contact_id',
            'save_customer_to_contacts',
        ], array_keys($this->errors($data, guest: true)));
    }

    #[Test]
    public function validation_fails_when_required_fields_missing(): void
    {
        $requiredFields = [
            'invoice_vs',
            'payment_method_id',
            'payment_currency',
            'issue_date',
            'due_in',
            'payment_status_id',
            'business_id',
        ];

        foreach ($requiredFields as $field) {
            $invalidData = $this->validInvoiceData;
            unset($invalidData[$field]);

            $this->assertArrayHasKey(
                $field,
                $this->errors($invalidData),
                "Should have error for missing {$field}",
            );
        }
    }

    #[Test]
    public function the_customer_needs_a_name_and_an_address(): void
    {
        $data = $this->validInvoiceData;
        unset($data['customer_name'], $data['customer_street'], $data['customer_city'], $data['customer_postal_code'], $data['customer_country']);

        $errors = $this->errors($data);

        foreach ([
            'customer_name',
            'customer_street',
            'customer_city',
            'customer_postal_code',
            'customer_country',
        ] as $field) {
            $this->assertArrayHasKey($field, $errors);
        }
    }

    #[Test]
    public function a_guest_invoice_names_the_missing_field_of_each_party(): void
    {
        $data = $this->guestInvoiceInput(['invoice-products' => [$this->item()]]);
        unset($data['supplier_name'], $data['supplier_street'], $data['customer_name'], $data['customer_street']);

        $errors = $this->errors($data, guest: true);

        $this->assertSame([
            'supplier_name',
            'supplier_street',
            'customer_name',
            'customer_street',
        ], array_keys($errors));
        $this->assertStringContainsString(__('parties.fields.street'), $errors['supplier_street'][0]);
        $this->assertStringContainsString(__('parties.fields.name'), $errors['customer_name'][0]);
    }

    #[Test]
    public function a_bank_transfer_needs_an_account_and_another_payment_method_does_not(): void
    {
        $data = ['bank_account_id' => null] + $this->validInvoiceData;
        $this->assertArrayHasKey('bank_account_id', $this->errors($data));

        $cash = PaymentMethod::where(
            'slug',
            '!=',
            PaymentMethodCode::BANK_ACCOUNT->value,
        )->firstOrFail();
        $this->assertSame([], $this->errors(['payment_method_id' => $cash->id] + $data));
    }

    #[Test]
    public function an_account_left_on_the_form_is_dropped_without_a_bank_transfer(): void
    {
        $cash = PaymentMethod::where('slug', PaymentMethodCode::CASH->value)->firstOrFail();
        [, $accountOfAnotherBusiness] = $this->issuerOf($this->user);

        $validator = $this->validator([
            'payment_method_id' => $cash->id,
            'bank_account_id' => $accountOfAnotherBusiness->id,
        ] + $this->validInvoiceData);
        $this->assertSame([], $validator->errors()->toArray());
        $this->assertArrayNotHasKey('bank_account_id', $validator->validated());

        $guestValidator = $this->validator(
            $this->guestInvoiceInput([
                'payment_method_id' => $cash->id,
                'account_number' => 'not an account',
                'invoice-products' => [$this->item()],
            ]),
            guest: true,
        );
        $this->assertSame([], $guestValidator->errors()->toArray());
        $this->assertSame([], array_filter(
            array_keys($guestValidator->validated()),
            fn (string $key): bool => str_starts_with($key, 'account_'),
        ));
    }

    #[Test]
    public function validation_fails_with_due_in_outside_one_to_maximum_days(): void
    {
        foreach ([
            0,
            -1,
            -10,
            InvoiceDueDate::MAX_DAYS + 1,
        ] as $value) {
            $this->assertArrayHasKey(
                'due_in',
                $this->errors(['due_in' => $value] + $this->validInvoiceData),
                "Validation should fail for due_in value: {$value}",
            );
        }
    }

    #[Test]
    public function validation_fails_with_invalid_currency_format(): void
    {
        $this->assertArrayHasKey('payment_currency', $this->errors(['payment_currency' => 'INVALID'] + $this->validInvoiceData));
    }

    #[Test]
    public function validation_fails_with_invalid_email_formats(): void
    {
        foreach ([
            'invalid-email',
            'test@',
            '@test.com',
            'test.com',
        ] as $email) {
            $this->assertArrayHasKey(
                'customer_email',
                $this->errors(['customer_email' => $email] + $this->validInvoiceData),
                "Validation should fail for email: {$email}",
            );
        }
    }

    #[Test]
    public function validation_fails_with_an_expense_status(): void
    {
        $data = ['payment_status_id' => Status::where('slug', 'paid-expense')->firstOrFail()->id] + $this->validInvoiceData;

        $this->assertSame(['payment_status_id'], array_keys($this->errors($data)));
    }

    #[Test]
    public function validation_fails_with_nonexistent_foreign_keys(): void
    {
        foreach ([
            'payment_method_id',
            'payment_status_id',
            'business_id',
            'bank_account_id',
            'contact_id',
        ] as $field) {
            $this->assertArrayHasKey(
                $field,
                $this->errors([$field => 99999] + $this->validInvoiceData),
                "Validation should fail for {$field} with an unknown id",
            );
        }
    }

    #[Test]
    public function the_business_account_and_contact_must_be_the_users_own(): void
    {
        [$foreignBusiness, $foreignAccount] = $this->issuerOf(User::factory()->create());
        [, $accountOfAnotherBusiness] = $this->issuerOf($this->user);
        $foreignContact = Contact::factory()->create();

        $this->assertArrayHasKey('business_id', $this->errors(['business_id' => $foreignBusiness->id] + $this->validInvoiceData));
        $this->assertArrayHasKey('bank_account_id', $this->errors(['bank_account_id' => $foreignAccount->id] + $this->validInvoiceData));
        $this->assertArrayHasKey('bank_account_id', $this->errors(['bank_account_id' => $accountOfAnotherBusiness->id] + $this->validInvoiceData));
        $this->assertArrayHasKey('contact_id', $this->errors(['contact_id' => $foreignContact->id] + $this->validInvoiceData));
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null(): void
    {
        $data = $this->validInvoiceData;
        foreach ([
            'invoice_ks',
            'invoice_ss',
            'invoice_text',
            'contact_id',
            'customer_email',
            'customer_phone',
            'customer_registration_country',
            'customer_company_number',
            'customer_vat_number',
        ] as $field) {
            $data[$field] = null;
        }

        $this->assertSame([], $this->errors($data));
    }

    #[Test]
    public function authorization_without_authenticated_user_returns_false(): void
    {
        $this->assertFalse((new InvoiceRequest)->authorize());
    }

    #[Test]
    public function authorization_with_authenticated_user_succeeds(): void
    {
        EntityLimit::create([
            'permission_name' => 'frontend.can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'description' => 'Invoice limit for frontend tests',
            'is_active' => true,
        ]);
        $this->actingAs($this->user, 'web');

        $this->assertTrue((new InvoiceRequest)->authorize());
    }

    #[Test]
    public function saving_the_customer_to_the_contacts_needs_the_contact_permission(): void
    {
        $this->actingAs($this->user, 'web');
        $request = InvoiceRequest::create(
            '/',
            'POST',
            ['save_customer_to_contacts' => '1'],
        );
        $request->setUserResolver(fn (): User => $this->user);

        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function validation_passes_with_valid_currencies(): void
    {
        foreach ([
            'CZK',
            'EUR',
            'USD',
            'GBP',
            'PLN',
            'HUF',
            'CHF',
        ] as $currency) {
            $data = [
                'payment_currency' => $currency,
                'invoice-products' => [$this->item(['currency' => $currency])],
            ] + $this->validInvoiceData;

            $this->assertSame(
                [],
                $this->errors($data),
                "Validation should pass for currency: {$currency}",
            );
        }
    }

    #[Test]
    public function validation_passes_with_valid_invoice_logo_file(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 100, 100)->size(1000);

        $this->assertSame([], $this->errors(['invoice_logo' => $file] + ['invoice-products' => [$this->invoiceItem()]] + $this->guestInvoiceInput(), guest: true));
    }

    #[Test]
    public function validation_fails_with_invalid_invoice_logo_format(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $this->assertArrayHasKey('invoice_logo', $this->errors(['invoice_logo' => $file] + ['invoice-products' => [$this->invoiceItem()]] + $this->guestInvoiceInput(), guest: true));
    }

    #[Test]
    public function validation_fails_with_oversized_invoice_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 100, 100)->size(3000);

        $this->assertArrayHasKey('invoice_logo', $this->errors(['invoice_logo' => $file] + ['invoice-products' => [$this->invoiceItem()]] + $this->guestInvoiceInput(), guest: true));
    }

    #[Test]
    public function validation_passes_with_null_invoice_logo(): void
    {
        $this->assertSame([], $this->errors(['invoice_logo' => null] + ['invoice-products' => [$this->invoiceItem()]] + $this->guestInvoiceInput(), guest: true));
    }

    #[Test]
    public function validation_passes_with_various_valid_image_formats(): void
    {
        foreach ([
            'jpg',
            'jpeg',
            'png',
            'gif',
            'svg',
        ] as $format) {
            $file = UploadedFile::fake()->image("logo.{$format}", 100, 100)->size(1000);

            $this->assertSame(
                [],
                $this->errors(['invoice_logo' => $file] + ['invoice-products' => [$this->invoiceItem()]] + $this->guestInvoiceInput(), guest: true),
                "Validation should pass with .{$format} format",
            );
        }
    }

    #[Test]
    public function an_invoice_needs_at_least_one_item(): void
    {
        foreach ([[], null] as $items) {
            $errors = $this->itemErrors($items ?? []);
            $this->assertSame([__('invoices.validation.amount_positive')], $errors['invoice-products'] ?? null);
        }
    }

    #[Test]
    public function a_valid_item_passes(): void
    {
        $this->assertSame([], $this->itemErrors([$this->item(), $this->item(['unit' => '', 'tax_rate' => '0'])]));
    }

    #[Test]
    public function an_item_quantity_must_be_positive_with_at_most_three_decimals(): void
    {
        foreach ([
            '0',
            '-1',
            '1.2345',
            'abc',
            '100000',
        ] as $quantity) {
            $errors = $this->itemErrors([$this->item(['quantity' => $quantity])]);
            $this->assertArrayHasKey(
                'invoice-products.0.quantity',
                $errors,
                'Accepted quantity ' . $quantity,
            );
        }
        $this->assertSame([], $this->itemErrors([$this->item(['quantity' => '0.125'])]));
    }

    #[Test]
    public function an_item_price_must_be_non_negative_with_at_most_two_decimals(): void
    {
        foreach ([
            '-1',
            '10.555',
            '100000000',
        ] as $price) {
            $errors = $this->itemErrors([$this->item(['price' => $price])]);
            $this->assertArrayHasKey(
                'invoice-products.0.price',
                $errors,
                'Accepted price ' . $price,
            );
        }
        $this->assertSame([], $this->itemErrors([$this->item(['price' => '0'])]));
    }

    #[Test]
    public function an_item_tax_rate_must_be_one_the_form_offers(): void
    {
        app()->setLocale('en');
        $errors = $this->itemErrors([$this->item(['tax_rate' => '17'])]);
        $this->assertSame(['An invoice item has an invalid VAT rate. Allowed rates: 0%, 12%, 21%.'], $errors['invoice-products.0.tax_rate'] ?? null);

        $this->assertSame([], $this->itemErrors([$this->item(['tax_rate' => '12']), $this->item(['tax_rate' => '0.00'])]));
    }

    #[Test]
    public function an_item_unit_must_be_a_known_unit(): void
    {
        $errors = $this->itemErrors([$this->item(['unit' => 'ks'])]);

        $this->assertArrayHasKey('invoice-products.0.unit', $errors);
    }

    #[Test]
    public function an_item_must_be_in_the_invoice_currency(): void
    {
        $errors = $this->itemErrors([$this->item(['currency' => 'EUR'])]);
        $this->assertSame([__('invoices.validation.item_currency')], $errors['invoice-products.0.currency'] ?? null);

        $errors = $this->itemErrors([$this->item(['currency' => 'XYZ'])], currency: 'XYZ');
        $this->assertArrayHasKey('invoice-products.0.currency', $errors);
        $this->assertArrayHasKey('payment_currency', $errors);
    }

    #[Test]
    public function an_item_may_reference_only_a_product_of_the_user(): void
    {
        $user = User::factory()->create();
        $own = Product::factory()->create(['user_id' => $user->id]);
        $foreign = Product::factory()->create();

        $this->assertSame([], $this->itemErrors([$this->item(['product_id' => $own->id])], $user));
        $this->assertArrayHasKey(
            'invoice-products.0.product_id',
            $this->itemErrors([$this->item(['product_id' => $foreign->id])], $user),
        );
    }

    #[Test]
    public function a_guest_invoice_rejects_items_sent_as_json_with_errors_per_item(): void
    {
        $items = [$this->item(), $this->item(['unit' => 'ks', 'product_id' => 1])];

        $response = $this->postJson(
            route('frontend.invoice.store.guest', ['locale' => 'cs']),
            $this->guestInvoiceInput(['invoice-products' => json_encode($items)]),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['invoice-products.1.unit', 'invoice-products.1.product_id'])
            ->assertJsonMissingValidationErrors(['invoice-products', 'invoice-products.0.unit']);
    }

    #[Test]
    public function items_that_are_not_json_are_rejected(): void
    {
        $response = $this->postJson(
            route('frontend.invoice.store.guest', ['locale' => 'cs']),
            $this->guestInvoiceInput(['invoice-products' => '{broken']),
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['invoice-products']);
    }

    #[Test]
    public function the_typed_payment_amount_is_neither_required_nor_kept(): void
    {
        foreach ([null, -100] as $amount) {
            $validator = $this->validator(['payment_amount' => $amount] + $this->validInvoiceData);

            $this->assertTrue($validator->passes());
            $this->assertArrayNotHasKey('payment_amount', $validator->validated());
        }
    }
}
