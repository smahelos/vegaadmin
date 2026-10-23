<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StatusCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorize only if authenticated backpack user has permission
        return backpack_auth()->check() && backpack_auth()->user()->can('can_create_edit_status');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Determine current model id for update scenario
        $id = $this->get('id') ?? $this->route('id') ?? $this->route('status_category');

        return [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:status_categories,slug,' . ($id ?? 'NULL'),
            'description' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('statuses.categories.fields.name'),
            'slug' => __('statuses.categories.fields.slug'),
            'description' => __('statuses.categories.fields.description'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Generate slug if not provided
        if (empty($this->slug) && ! empty($this->name)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
