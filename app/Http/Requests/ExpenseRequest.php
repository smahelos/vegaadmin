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
            'expense_date' => __('expenses.fields.expense_date'),
            'amount' => __('expenses.fields.amount'),
            'currency' => __('expenses.fields.currency'),
            'supplier_id' => __('expenses.fields.supplier_id'),
            'category_id' => __('expenses.fields.category_id'),
            'payment_method_id' => __('expenses.fields.payment_method_id'),
            'reference_number' => __('expenses.fields.reference_number'),
            'description' => __('expenses.fields.description'),
            'receipt_file' => __('expenses.fields.receipt_file'),
            'attachments' => __('expenses.fields.attachments'),
            'attachments.*' => __('expenses.fields.attachments'),
            'tax_amount' => __('expenses.fields.tax_amount'),
            'status_id' => __('expenses.fields.status_id'),
            'user_id' => __('expenses.fields.user_id'),
            'tax_included' => __('expenses.fields.tax_included'),
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
