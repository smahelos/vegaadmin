<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryRequest extends FormRequest
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
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $id = $this->route('id');
        if (!$id && $this->has('id')) {
            $id = $this->get('id');
        }
        $id = $id ?: 'NULL';

        return [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:expense_categories,slug,' . $id,
            'color' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('expenses.categories.fields.name'),
            'slug' => __('expenses.categories.fields.slug'),
            'color' => __('expenses.categories.fields.color'),
            'description' => __('expenses.categories.fields.description'),
            'is_active' => __('expenses.categories.fields.is_active'),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
