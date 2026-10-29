<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Http\Requests\Party\DocumentPartyInput;
use App\Http\Requests\Rules\InvoiceStatusId;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class InvoiceRequest extends BaseEntityRequest
{
    /**
     * Override authorization to allow guest invoice creation on specific route.
     * Guests can access the guest store endpoint (temporary invoice creation) without authentication.
     */
    public function authorize(): bool
    {
        if ($this->isGuestStore()) {
            return true; // Skip parent authorization & limit checks for guest temporary invoices
        }

        if ($this->boolean('save_customer_to_contacts') && $this->user()?->can('frontend.can_create_contact') !== true) {
            return false;
        }

        return parent::authorize();
    }

    /**
     * Customize failed validation for guest JSON endpoint to avoid redirect (302) and return 422 JSON.
     */
    protected function failedValidation(Validator $validator): never
    {
        if ($this->isGuestStore()) {
            // For guest JSON endpoint, return 422 JSON response
            $response = response()->json([
                'message' => __('invoices.messages.validation_failed'),
                'errors' => $validator->errors()->toArray(),
            ], 422);
            throw new ValidationException($validator, $response);
        }

        // For regular routes, show both field errors and general error message
        throw new ValidationException($validator,
            redirect()->back()
                ->withErrors($validator->errors())
                ->withInput()
                ->with('error', __('invoices.messages.validation_failed')),
        );
    }

    /**
     * Force JSON expectation for guest store route so Laravel returns 422 JSON instead of redirect.
     */
    public function expectsJson(): bool
    {
        if ($this->isGuestStore()) {
            return true;
        }

        return parent::expectsJson();
    }

    public function wantsJson(): bool
    {
        if ($this->isGuestStore()) {
            return true;
        }

        return parent::wantsJson();
    }

    /**
     * Get the entity type for limit checking
     */
    protected function getEntityType(): string
    {
        return 'invoice';
    }

    /**
     * Get required permission for invoice operations
     * Note: Invoice creation from frontend is currently unlimited
     */
    protected function getRequiredPermission(): string
    {
        return ''; // No permission required - frontend invoice creation is unlimited
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Get max file size from config
        $maxFileSize = Config::get('file_upload.contexts.invoice_logo.max_kb', 2048);

        $rules = [
            'invoice_vs' => 'required|string|max:50',
            'invoice_ks' => 'nullable|string|max:20',
            'invoice_ss' => 'nullable|string|max:20',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_currency' => ['required', Rule::enum(Currency::class)],
            'issue_date' => 'required|date',
            'tax_point_date' => 'required|date',
            'due_in' => 'required|integer|min:1|max:' . InvoiceDueDate::MAX_DAYS,
            'payment_status_id' => [
                'required',
                InvoiceStatusId::rule(),
            ],

            // Invoice text
            'invoice_text' => 'nullable|string',

            // Ensure only allowed file types are accepted; allow SVG as image format
            'invoice_logo' => $this->isGuestStore() ? 'nullable|file|mimes:jpeg,jpg,png,gif,svg,webp|max:' . $maxFileSize : ['exclude'],

            'template' => 'nullable|string|in:default,modern,minimal', // Add template validation

            'invoice-products' => [
                'required',
                'array',
                'min:1',
            ],
            'invoice-products.*' => ['array'],
            'invoice-products.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'invoice-products.*.quantity' => [
                'required',
                'numeric',
                'decimal:0,3',
                'gt:0',
                'max:99999.999',
            ],
            'invoice-products.*.unit' => ['nullable', Rule::enum(Unit::class)],
            'invoice-products.*.price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:99999999.99',
            ],
            'invoice-products.*.currency' => [
                'required',
                Rule::enum(Currency::class),
                'same:payment_currency',
            ],
            'invoice-products.*.tax_rate' => [
                'required',
                'numeric',
                $this->taxRateRule(),
            ],
            'invoice-products.*.product_id' => $this->isGuestStore()
                ? ['prohibited']
                : [
                    'nullable',
                    'integer',
                    Rule::exists('products', 'id')->where('user_id', $this->user()?->getAuthIdentifier()),
                ],
            'invoice-products.*.category' => [
                'nullable',
                'string',
                'max:255',
            ],
            'invoice-products.*.description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

        return [...$rules, ...$this->partyRules()];
    }

    /**
     * A signed-in user selects the business and its account and picks or enters the customer; a guest enters
     * both parties and the account.
     *
     * @return array<string, mixed>
     */
    private function partyRules(): array
    {
        $input = $this->all();
        $bankTransfer = $this->paysByBankTransfer();

        if ($this->isGuestStore()) {
            return [
                'business_id' => ['prohibited'],
                'bank_account_id' => ['prohibited'],
                'contact_id' => ['prohibited'],
                'save_customer_to_contacts' => ['prohibited'],
                ...DocumentPartyInput::rules(
                    $input,
                    'supplier',
                    true,
                ),
                ...DocumentPartyInput::rules(
                    $input,
                    'customer',
                    true,
                ),
                ...DocumentPartyInput::accountRules($input, $bankTransfer),
            ];
        }

        $userId = $this->user()?->getAuthIdentifier();

        return [
            'business_id' => [
                'required',
                'integer',
                Rule::exists('businesses', 'id')->where('user_id', $userId),
            ],
            'bank_account_id' => $bankTransfer ? [
                'required',
                'integer',
                Rule::exists('bank_accounts', 'id')->where('business_id', $this->integer('business_id')),
            ] : ['exclude'],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('user_id', $userId),
            ],
            'save_customer_to_contacts' => ['boolean'],
            'reload_business' => ['boolean'],
            ...DocumentPartyInput::rules(
                $input,
                'customer',
                true,
            ),
        ];
    }

    private function paysByBankTransfer(): bool
    {
        $paymentMethodId = $this->integer('payment_method_id');
        if ($paymentMethodId <= 0) {
            return false;
        }

        $paymentMethod = app(PaymentMethodDtoReadRepositoryInterface::class)->findById(PaymentMethodId::fromInt($paymentMethodId));

        return $paymentMethod?->code === PaymentMethodCode::BANK_ACCOUNT;
    }

    /**
     * The parties a signed-in user chose, from the validated input.
     */
    public function partySelection(): InvoicePartySelection
    {
        $validated = $this->validated();
        $bankAccountId = $validated['bank_account_id'] ?? null;
        $contactId = $validated['contact_id'] ?? null;

        return new InvoicePartySelection(
            businessId: BusinessId::fromInt((int) $validated['business_id']),
            bankAccountId: $bankAccountId === null ? null : BankAccountId::fromInt((int) $bankAccountId),
            contactId: $contactId === null ? null : ContactId::fromInt((int) $contactId),
            customer: DocumentPartyInput::party($validated, 'customer'),
            saveCustomerToContacts: $contactId === null && $this->boolean('save_customer_to_contacts'),
            reloadBusiness: $this->boolean('reload_business'),
        );
    }

    /**
     * A party entered on the form, the guest's supplier or customer.
     */
    public function oneOffParty(string $role): DocumentPartyDTO
    {
        return DocumentPartyInput::party($this->validated(), $role);
    }

    /**
     * The account a guest entered, null without one.
     */
    public function oneOffAccount(): ?DocumentAccountDTO
    {
        return DocumentPartyInput::account($this->validated());
    }

    /**
     * A line may carry only a rate the invoice form offers: 0 % or one of the VAT rates in the taxes table.
     * The rates are loaded once, when the first numeric rate reaches the rule.
     */
    private function taxRateRule(): Closure
    {
        $allowed = null;

        return function (
            string $attribute,
            mixed $value,
            Closure $fail,
        ) use (&$allowed): void {
            if (! is_numeric($value)) {
                return;
            }
            $allowed ??= $this->allowedTaxRates();
            if (! in_array(
                $this->normalizedRate((string) $value),
                $allowed,
                true,
            )) {
                $fail(__('invoices.validation.tax_rate', ['rates' => $this->formattedRates($allowed)]));
            }
        };
    }

    /**
     * @return list<string>
     */
    private function allowedTaxRates(): array
    {
        $rates = array_map(
            fn (int|float|string $rate): string => $this->normalizedRate((string) $rate),
            ['0', ...array_values(app(TaxDtoReadRepositoryInterface::class)->getDphRatesForDropdown())],
        );
        $rates = array_values(array_unique($rates));
        sort($rates, SORT_NUMERIC);

        return $rates;
    }

    /**
     * @param  list<string>  $rates
     */
    private function formattedRates(array $rates): string
    {
        return implode(', ', array_map(
            fn (string $rate): string => (string) Number::percentage(
                (float) $rate,
                maxPrecision: 2,
                locale: app()->getLocale(),
            ),
            $rates,
        ));
    }

    /** "21", "21.0" and "21.00" are the same rate. */
    private function normalizedRate(string $rate): string
    {
        $rate = ltrim(trim($rate), '+');
        if (str_contains($rate, '.')) {
            $rate = rtrim(rtrim($rate, '0'), '.');
        }

        return $rate === '' ? '0' : $rate;
    }

    private function isGuestStore(): bool
    {
        // Tests pass a plain object with a name property instead of an Illuminate route
        $route = $this->route();
        $name = match (true) {
            is_object($route) && method_exists($route, 'getName') => $route->getName(),
            is_object($route) && property_exists($route, 'name') => $route->name,
            default => null,
        };

        return $name === 'frontend.invoice.store.guest';
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invoice_vs' => __('invoices.fields.invoice_vs'),
            'invoice_ks' => __('invoices.fields.invoice_ks'),
            'invoice_ss' => __('invoices.fields.invoice_ss'),
            'payment_method_id' => __('invoices.fields.payment_method_id'),
            'payment_currency' => __('invoices.fields.payment_currency'),
            'issue_date' => __('invoices.fields.issue_date'),
            'tax_point_date' => __('invoices.fields.tax_point_date'),
            'due_in' => __('invoices.fields.due_in'),
            'payment_status_id' => __('invoices.fields.status'),
            'invoice_text' => __('invoices.fields.invoice_text'),
            'invoice_logo' => __('invoices.fields.invoice_logo'),
            'template' => __('invoices.fields.template'),
            'invoice-products' => __('invoices.fields.invoice_items'),
            'invoice-products.*.name' => __('invoices.fields.item_name'),
            'invoice-products.*.quantity' => __('invoices.fields.item_quantity'),
            'invoice-products.*.unit' => __('invoices.fields.item_unit'),
            'invoice-products.*.price' => __('invoices.fields.item_price'),
            'invoice-products.*.currency' => __('invoices.fields.item_currency'),
            'invoice-products.*.tax_rate' => __('invoices.fields.item_tax_rate'),
            'invoice-products.*.product_id' => __('invoices.fields.item_product'),
            'invoice-products.*.category' => __('invoices.fields.item_category'),
            'invoice-products.*.description' => __('invoices.fields.item_description'),
            'business_id' => __('invoices.fields.business_id'),
            'bank_account_id' => __('invoices.fields.bank_account_id'),
            'contact_id' => __('invoices.fields.contact_id'),
            ...DocumentPartyInput::attributes('supplier'),
            ...DocumentPartyInput::attributes('customer'),
            ...DocumentPartyInput::accountAttributes(),
        ];
    }

    /**
     * An item must be in the currency of the invoice; the standard messages cannot say that.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice-products.required' => __('invoices.validation.amount_positive'),
            'invoice-products.min' => __('invoices.validation.amount_positive'),
            'invoice-products.*.currency.same' => __('invoices.validation.item_currency'),
        ];
    }

    /**
     * Fills empty fields from their *_fallback inputs and decodes the items.
     */
    protected function prepareForValidation(): void
    {
        // Call parent preparation first
        parent::prepareForValidation();

        // IMPORTANT: Never replace the whole input with $this->all()
        // because it can include UploadedFile instances and move them out of the files bag.
        // Instead, build a minimal diff and merge it to preserve uploaded files.

        $merge = [];

        // Handle any other fallback fields
        foreach ([
            'payment_method_id',
            'due_in',
            'payment_status_id',
        ] as $field) {
            if (! $this->filled($field) && $this->has($field . '_fallback')) {
                $merge[$field] = $this->input($field . '_fallback');
            }
        }

        // The form sends the items as one JSON field; a value that is not JSON stays and fails the array rule.
        $items = $this->input('invoice-products');
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (is_array($decoded)) {
                $merge['invoice-products'] = $decoded;
            }
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
