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

        return $user->can('can_create_edit_expense');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category_id' => 'required|exists:expense_categories,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'reference_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'receipt_file' => 'nullable|sometimes|file|max:10240', // Max 10MB
            'tax_amount' => 'nullable|numeric|min:0',
            'status_id' => 'nullable|exists:statuses,id',
            'user_id' => 'required|exists:users,id',
        ];
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
}
