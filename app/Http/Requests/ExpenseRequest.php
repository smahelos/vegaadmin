<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage expenses
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->can('frontend.can_create_edit_expense');
    }

    /**
     * Get the validation rules that apply to the request.
     * Adds parity fields (attachments, tax_included) and conditional sometimes rules for update.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $rules = [
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category_id' => 'required|exists:expense_categories,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'reference_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'receipt_file' => 'nullable|sometimes|file|max:10240', // Max 10MB
            'attachments' => 'sometimes|array',
            'attachments.*' => 'sometimes|string',
            'tax_amount' => 'nullable|numeric|min:0',
            'status_id' => 'nullable|exists:statuses,id',
            'user_id' => 'required|exists:users,id',
            'tax_included' => 'nullable|boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            foreach (['expense_date','amount','currency','category_id','user_id'] as $field) {
                $rules[$field] = str_replace('required','sometimes',$rules[$field]);
            }
            if (isset($rules['description']) && !str_contains($rules['description'], 'sometimes')) {
                $rules['description'] = 'sometimes|string';
            }
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expense_date' => trans('admin.expenses.date'),
            'amount' => trans('admin.expenses.amount'),
            'currency' => trans('admin.expenses.currency'),
            'supplier_id' => trans('admin.expenses.supplier'),
            'category_id' => trans('admin.expenses.category'),
            'payment_method_id' => trans('admin.expenses.payment_method'),
            'reference_number' => trans('admin.expenses.reference_number'),
            'description' => trans('admin.expenses.description'),
            'receipt_file' => trans('admin.expenses.receipt'),
            'attachments' => trans('admin.expenses.attachments'),
            'attachments.*' => trans('admin.expenses.attachments'),
            'tax_amount' => trans('admin.expenses.tax_amount'),
            'status_id' => trans('admin.expenses.status'),
            'tax_included' => trans('admin.expenses.tax_included'),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     * Uses generic Laravel lines with translated attributes for consistency.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expense_date.required' => __('validation.required', ['attribute' => trans('admin.expenses.date')]),
            'expense_date.date' => __('validation.date', ['attribute' => trans('admin.expenses.date')]),
            'amount.required' => __('validation.required', ['attribute' => trans('admin.expenses.amount')]),
            'amount.numeric' => __('validation.numeric', ['attribute' => trans('admin.expenses.amount')]),
            'amount.min' => __('validation.min.numeric', ['attribute' => trans('admin.expenses.amount'), 'min' => 0]),
            'currency.required' => __('validation.required', ['attribute' => trans('admin.expenses.currency')]),
            'currency.size' => __('validation.size.string', ['attribute' => trans('admin.expenses.currency'), 'size' => 3]),
            'supplier_id.exists' => __('validation.exists', ['attribute' => trans('admin.expenses.supplier')]),
            'category_id.required' => __('validation.required', ['attribute' => trans('admin.expenses.category')]),
            'category_id.exists' => __('validation.exists', ['attribute' => trans('admin.expenses.category')]),
            'payment_method_id.exists' => __('validation.exists', ['attribute' => trans('admin.expenses.payment_method')]),
            'reference_number.max' => __('validation.max.string', ['attribute' => trans('admin.expenses.reference_number'), 'max' => 255]),
            'receipt_file.file' => __('validation.file', ['attribute' => trans('admin.expenses.receipt')]),
            'receipt_file.max' => __('validation.max.file', ['attribute' => trans('admin.expenses.receipt'), 'max' => 10240]),
            'tax_amount.numeric' => __('validation.numeric', ['attribute' => trans('admin.expenses.tax_amount')]),
            'tax_amount.min' => __('validation.min.numeric', ['attribute' => trans('admin.expenses.tax_amount'), 'min' => 0]),
            'status_id.exists' => __('validation.exists', ['attribute' => trans('admin.expenses.status')]),
            'user_id.required' => __('validation.required', ['attribute' => trans('admin.expenses.user')]),
            'user_id.exists' => __('validation.exists', ['attribute' => trans('admin.expenses.user')]),
            'attachments.array' => __('validation.array', ['attribute' => trans('admin.expenses.attachments')]),
            'attachments.*.string' => __('validation.string', ['attribute' => trans('admin.expenses.attachments')]),
            'tax_included.boolean' => __('validation.boolean', ['attribute' => trans('admin.expenses.tax_included')]),
        ];
    }

    /**
     * Normalize data before validation for parity with admin request.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('currency') && is_string($this->currency)) {
            $this->merge(['currency' => strtoupper($this->currency)]);
        }
        if ($this->has('reference_number') && is_string($this->reference_number)) {
            $this->merge(['reference_number' => trim($this->reference_number)]);
        }
    }
}
