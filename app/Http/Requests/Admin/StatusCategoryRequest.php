<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StatusCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage statuses
        return backpack_user()->can('can_create_edit_status');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->get('id') ?? 'NULL';
        
        return [
            'name' => 'required|min:2|max:255',
            'slug' => 'required|min:2|max:255|unique:status_categories,slug,'.$id,
            'description' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.status_categories.name'),
            'slug' => trans('admin.status_categories.slug'),
            'description' => trans('admin.status_categories.description'),
        ];
    }
}
