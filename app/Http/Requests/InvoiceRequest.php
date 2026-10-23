<?php

namespace App\Http\Requests;

use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Unit\Enums\Unit;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InvoiceRequest extends BaseEntityRequest
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
        return parent::authorize();
    }

    /**
     * Customize failed validation for guest JSON endpoint to avoid redirect (302) and return 422 JSON.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {

        if ($this->isGuestStore()) {
            // For guest JSON endpoint, return 422 JSON response
            $response = response()->json([
                'message' => __('invoices.messages.validation_failed'),
                'errors' => $validator->errors()->toArray(),
            ], 422);
            throw new \Illuminate\Validation\ValidationException($validator, $response);
        }

        // For regular routes, show both field errors and general error message
        throw new \Illuminate\Validation\ValidationException($validator,
            redirect()->back()
                ->withErrors($validator->errors())
                ->withInput()
                ->with('error', trans('invoices.messages.validation_failed'))
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
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        // Get max file size from config
        $maxFileSize = \Illuminate\Support\Facades\Config::get('file_upload.contexts.invoice_logo.max_kb', 2048);

        $rules = [
            'invoice_vs' => 'required|string|max:50',
            'invoice_ks' => 'nullable|string|max:20',
            'invoice_ss' => 'nullable|string|max:20',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_currency' => ['required', Rule::enum(Currency::class)],
            'issue_date' => 'required|date',
            'tax_point_date' => 'nullable|date',
            'due_in' => 'required|integer|min:1',
            'payment_status_id' => 'required|exists:statuses,id',

            // Issuer details
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id', 'required_without:name'],
            'name' => ['nullable', 'string', 'min:3', 'max:255', 'required_without:supplier_id'],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'street' => ['nullable', 'string', 'max:255', 'required_without:supplier_id'],
            'city' => ['nullable', 'string', 'max:255', 'required_without:supplier_id'],
            'zip' => ['nullable', 'string', 'max:20', 'required_without:supplier_id'],
            'country' => ['nullable', 'string', 'max:255', 'required_without:supplier_id'],
            'ico' => 'nullable|string|max:50',
            'dic' => 'nullable|string|max:50',
            'supplier_shortcut' => 'nullable|string|max:50',

            // Bank account details
            'account_number' => 'nullable|string|max:255',
            'bank_code' => 'nullable|string|max:10',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'swift' => 'nullable|string|max:11',

            // Client details
            'client_id' => ['nullable', 'integer', 'exists:clients,id', 'required_without:client_name'],
            'client_name' => ['nullable', 'string', 'min:3', 'max:255', 'required_without:client_id'],
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'client_street' => ['nullable', 'string', 'max:255', 'required_without:client_id'],
            'client_city' => ['nullable', 'string', 'max:255', 'required_without:client_id'],
            'client_zip' => ['nullable', 'string', 'max:20', 'required_without:client_id'],
            'client_country' => ['nullable', 'string', 'max:255', 'required_without:client_id'],
            'client_ico' => 'nullable|string|max:50',
            'client_dic' => 'nullable|string|max:50',
            'client_shortcut' => 'nullable|string|max:50',

            // Invoice text
            'invoice_text' => 'nullable|string',

            // Ensure only allowed file types are accepted; allow SVG as image format
            'invoice_logo' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg,webp|max:' . $maxFileSize,

            'template' => 'nullable|string|in:default,modern,minimal', // Add template validation

            'invoice-products' => ['required', 'array', 'min:1'],
            'invoice-products.*' => ['array'],
            'invoice-products.*.name' => ['required', 'string', 'max:255'],
            'invoice-products.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:99999.999'],
            'invoice-products.*.unit' => ['nullable', Rule::enum(Unit::class)],
            'invoice-products.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'invoice-products.*.currency' => ['required', Rule::enum(Currency::class), 'same:payment_currency'],
            'invoice-products.*.tax_rate' => ['required', 'numeric', $this->taxRateRule()],
            'invoice-products.*.product_id' => $this->isGuestStore()
                ? ['prohibited']
                : ['nullable', 'integer', Rule::exists('products', 'id')->where('user_id', $this->user()?->getAuthIdentifier())],
            'invoice-products.*.category' => ['nullable', 'string', 'max:255'],
            'invoice-products.*.description' => ['nullable', 'string', 'max:255'],
        ];

        return $rules;
    }

    /**
     * A line may carry only a rate the invoice form offers: 0 % or one of the VAT rates in the taxes table.
     */
    private function taxRateRule(): Closure
    {
        $taxes = app(TaxDtoReadRepositoryInterface::class);
        $allowed = array_map(
            fn (int|float|string $rate): string => $this->normalizedRate((string) $rate),
            ['0', ...array_values($taxes->getDphRatesForDropdown())]
        );

        return function (string $attribute, mixed $value, Closure $fail) use ($allowed): void {
            if (is_numeric($value) && ! in_array($this->normalizedRate((string) $value), $allowed, true)) {
                $fail(__('invoices.validation.tax_rate'));
            }
        };
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
     * Get the validation attributes for error messages
     *
     * @return array
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
            'supplier_id' => __('invoices.fields.supplier_id'),
            'name' => __('invoices.fields.supplier_name'),
            'email' => __('invoices.fields.supplier_email'),
            'phone' => __('invoices.fields.supplier_phone'),
            'street' => __('invoices.fields.supplier_street'),
            'city' => __('invoices.fields.supplier_city'),
            'zip' => __('invoices.fields.supplier_zip'),
            'country' => __('invoices.fields.supplier_country'),
            'ico' => __('invoices.fields.supplier_ico'),
            'dic' => __('invoices.fields.supplier_dic'),
            'supplier_shortcut' => __('invoices.fields.supplier_shortcut'),
            'account_number' => __('suppliers.fields.account_number'),
            'bank_code' => __('suppliers.fields.bank_code'),
            'bank_name' => __('suppliers.fields.bank_name'),
            'iban' => __('suppliers.fields.iban'),
            'swift' => __('suppliers.fields.swift'),
            'client_id' => __('invoices.fields.client_id'),
            'client_name' => __('invoices.fields.client_name'),
            'client_email' => __('invoices.fields.client_email'),
            'client_phone' => __('invoices.fields.client_phone'),
            'client_street' => __('invoices.fields.client_street'),
            'client_city' => __('invoices.fields.client_city'),
            'client_zip' => __('invoices.fields.client_zip'),
            'client_country' => __('invoices.fields.client_country'),
            'client_ico' => __('invoices.fields.client_ico'),
            'client_dic' => __('invoices.fields.client_dic'),
            'client_shortcut' => __('invoices.fields.client_shortcut'),
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
        ];
    }

    /**
     * Choosing an existing party or filling in a new one is one decision, and an item must be
     * in the currency of the invoice; the standard messages cannot say that.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required_without' => __('invoices.validation.supplier_required_without'),
            'client_id.required_without' => __('invoices.validation.client_required_without'),
            'invoice-products.required' => __('invoices.validation.amount_positive'),
            'invoice-products.min' => __('invoices.validation.amount_positive'),
            'invoice-products.*.currency.same' => __('invoices.validation.item_currency'),
        ];
    }

    /**
     * Prepare the data for validation.
     * This method merges fallback values for form fields when original ones are missing.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Call parent preparation first
        parent::prepareForValidation();

        // IMPORTANT: Never replace the whole input with $this->all()
        // because it can include UploadedFile instances and move them out of the files bag.
        // Instead, build a minimal diff and merge it to preserve uploaded files.

        $merge = [];

        // Handle country fields
        if (!$this->filled('country') && $this->has('country_fallback')) {
            $merge['country'] = $this->input('country_fallback');
        }

        if (!$this->filled('client_country') && $this->has('client_country_fallback')) {
            $merge['client_country'] = $this->input('client_country_fallback');
        }

        // Handle bank_code field
        if (!$this->filled('bank_code') && $this->has('bank_code_fallback')) {
            $merge['bank_code'] = $this->input('bank_code_fallback');
        }

        // Handle any other fallback fields
        foreach (['payment_method_id', 'supplier_id', 'client_id', 'due_in', 'payment_status_id'] as $field) {
            if (!$this->filled($field) && $this->has($field . '_fallback')) {
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

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
