<?php

namespace App\Http\Requests;

use App\Domain\Shared\Unit\Enums\Unit;
use Closure;
use Illuminate\Support\Facades\Auth;

class InvoiceRequest extends BaseEntityRequest
{
    /**
     * Override authorization to allow guest invoice creation on specific route.
     * Guests can access the guest store endpoint (temporary invoice creation) without authentication.
     */
    public function authorize(): bool
    {
        // Allow guest access for the guest invoice creation endpoint
        $route = $this->route();
        $routeName = null;
        if ($route) {
            // Support both Illuminate Route and simple stdClass used in tests
            if (is_object($route) && method_exists($route, 'getName')) {
                $routeName = $route->getName();
            } elseif (is_object($route) && property_exists($route, 'name')) {
                $routeName = $route->name;
            }
        }
        if ($routeName === 'frontend.invoice.store.guest') {
            return true; // Skip parent authorization & limit checks for guest temporary invoices
        }
        return parent::authorize();
    }

    /**
     * Customize failed validation for guest JSON endpoint to avoid redirect (302) and return 422 JSON.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $route = $this->route();
        $routeName = (is_object($route) && method_exists($route, 'getName')) ? $route->getName() : (is_object($route) && property_exists($route, 'name') ? $route->name : null);

        if ($routeName === 'frontend.invoice.store.guest') {
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
        $route = $this->route();
        $routeName = (is_object($route) && method_exists($route, 'getName')) ? $route->getName() : (is_object($route) && property_exists($route, 'name') ? $route->name : null);
        if ($routeName === 'frontend.invoice.store.guest') {
            return true;
        }
        return parent::expectsJson();
    }

    public function wantsJson(): bool
    {
        $route = $this->route();
        $routeName = (is_object($route) && method_exists($route, 'getName')) ? $route->getName() : (is_object($route) && property_exists($route, 'name') ? $route->name : null);
        if ($routeName === 'frontend.invoice.store.guest') {
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
            'payment_amount' => 'required|numeric|min:0',
            'payment_currency' => 'required|string|max:3',
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

            'invoice-products' => ['nullable', $this->itemUnitsRule()],
        ];

        return $rules;
    }

    /**
     * The items arrive as one JSON field; each unit must be empty or a Unit value.
     */
    private function itemUnitsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $items = is_string($value) ? json_decode($value, true) : $value;

            foreach (is_array($items) ? $items : [] as $item) {
                $unit = is_array($item) ? ($item['unit'] ?? null) : null;

                if ($unit !== null && $unit !== '' && (! is_string($unit) || Unit::tryFrom($unit) === null)) {
                    $fail(__('invoices.validation.unit'));

                    return;
                }
            }
        };
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
            'payment_amount' => __('invoices.fields.payment_amount'),
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
        ];
    }

    /**
     * Choosing an existing party or filling in a new one is one decision; the standard
     * required_without message cannot say that.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required_without' => __('invoices.validation.supplier_required_without'),
            'client_id.required_without' => __('invoices.validation.client_required_without'),
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

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
