<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage statuses
        return backpack_user()->can('can_create_edit_status');
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('statuses', 'slug')->ignore($this->id),
            ],
            'category_id' => 'required|exists:status_categories,id',
            'color' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => __('statuses.fields.name'),
            'slug' => __('statuses.fields.slug'),
            'category_id' => __('statuses.fields.category'),
            'color' => __('statuses.fields.color'),
            'description' => __('statuses.fields.description'),
            'is_active' => __('statuses.fields.is_active'),
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
            'name.required' => __('statuses.validation.name_required'),
            'slug.required' => __('statuses.validation.slug_required'),
            'slug.unique' => __('statuses.validation.slug_unique'),
        ];
    }
}
