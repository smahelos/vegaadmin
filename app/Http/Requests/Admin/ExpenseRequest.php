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
            'expense_date' => __('expenses.fields.expense_date'),
            'amount' => __('expenses.fields.amount'),
            'currency' => __('expenses.fields.currency'),
            'supplier_id' => __('expenses.fields.supplier_id'),
            'category_id' => __('expenses.fields.category_id'),
            'payment_method_id' => __('expenses.fields.payment_method_id'),
            'reference_number' => __('expenses.fields.reference_number'),
            'description' => __('expenses.fields.description'),
            'attachments' => __('expenses.fields.attachments'),
            'attachments.*' => __('expenses.fields.attachments'),
            'tax_amount' => __('expenses.fields.tax_amount'),
            'status_id' => __('expenses.fields.status_id'),
            'user_id' => __('expenses.fields.user_id'),
            'tax_included' => __('expenses.fields.tax_included'),
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
