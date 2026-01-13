<?php

namespace App\Http\Requests;

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
                'message' => __('validation.failed'),
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
        ];

        return $rules;
    }

    /**
     * Get the validation attributes for error messages
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            //
            'invoice_logo' => __('invoices.fields.invoice_logo'),
        ];
    }

    /**
     * Get custom error messages for validation rules
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // Invoice basic details
            'invoice_vs.required' => __('invoices.validation.invoice_vs_required'),
            'due_in.required' => __('invoices.validation.due_in_required'),
            'payment_method_id.required' => __('invoices.validation.payment_method_required'),
            'payment_amount.required' => __('invoices.validation.amount_required'),
            'payment_amount.numeric' => __('invoices.validation.amount_numeric'),
            'payment_amount.min' => __('invoices.validation.amount_min'),
            'payment_currency.required' => __('invoices.validation.currency_required'),
            'issue_date.required' => __('invoices.validation.issue_date_required'),
            'payment_status_id.required' => __('invoices.validation.payment_status_required'),
            'invoice_logo.file' => __('invoices.validation.invoice_logo_file'),
            'invoice_logo.mimes' => __('invoices.validation.invoice_logo_format'),
            'invoice_logo.max' => __('invoices.validation.invoice_logo_size'),

            // Supplier/issuer validation
            'supplier_id.required_without' => __('invoices.validation.supplier_required_without'),
            'name.required_without' => __('invoices.validation.supplier_name_required'),
            'name.min' => __('invoices.validation.supplier_name_min'),
            'street.required_without' => __('invoices.validation.street_required'),
            'city.required_without' => __('invoices.validation.city_required'),
            'zip.required_without' => __('invoices.validation.zip_required'),
            'country.required_without' => __('invoices.validation.country_required'),

            // Client validation
            'client_id.required_without' => __('invoices.validation.client_required_without'),
            'client_name.required_without' => __('invoices.validation.client_name_required'),
            'client_name.min' => __('invoices.validation.client_name_min'),
            'client_street.required_without' => __('invoices.validation.client_street_required'),
            'client_city.required_without' => __('invoices.validation.client_city_required'),
            'client_zip.required_without' => __('invoices.validation.client_zip_required'),
            'client_country.required_without' => __('invoices.validation.client_country_required'),
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
