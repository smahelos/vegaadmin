<?php

namespace App\Http\Requests\Admin;

/**
 * Admin Expense Request
 * Implements permission & entity limit enforcement via BaseEntityRequest.
 * Permission: can_create_edit_expense
 * Entity type: expense
 */
class ExpenseRequest extends BaseEntityRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected function getRequiredPermission(): string
    {
        return 'can_create_edit_expense';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
            // Ensure description is marked as sometimes so it is validated when alone
            if (isset($rules['description']) && !str_contains($rules['description'], 'sometimes')) {
                $rules['description'] = 'sometimes|string';
            }
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
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
            'tax_amount' => trans('admin.expenses.tax_amount'),
            'status_id' => trans('admin.expenses.status'),
            'tax_included' => trans('admin.expenses.tax_included'),
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'expense_date.required' => __('admin.expenses.validation.date_required'),
            'amount.required' => __('admin.expenses.validation.amount_required'),
            'amount.numeric' => __('admin.expenses.validation.amount_numeric'),
            'currency.required' => __('admin.expenses.validation.currency_required'),
            'category_id.required' => __('admin.expenses.validation.category_required'),
            'user_id.required' => __('admin.expenses.validation.user_required'),
        ];
    }

    /**
     * Normalize data before validation.
     */
    public function prepareForValidation(): void
    {
        if ($this->has('currency') && is_string($this->currency)) {
            $this->merge(['currency' => strtoupper($this->currency)]);
        }
        if ($this->has('reference_number') && is_string($this->reference_number)) {
            $this->merge(['reference_number' => trim($this->reference_number)]);
        }
    }
}
