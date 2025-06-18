<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StatusCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage statuses
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->can('can_create_edit_status');
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

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('admin.validation.required', ['field' => trans('admin.status_categories.name')]),
            'name.min' => trans('admin.validation.min', ['field' => trans('admin.status_categories.name'), 'min' => 2]),
            'name.max' => trans('admin.validation.max', ['field' => trans('admin.status_categories.name'), 'max' => 255]),
            'slug.required' => trans('admin.validation.required', ['field' => trans('admin.status_categories.slug')]),
            'slug.min' => trans('admin.validation.min', ['field' => trans('admin.status_categories.slug'), 'min' => 2]),
            'slug.max' => trans('admin.validation.max', ['field' => trans('admin.status_categories.slug'), 'max' => 255]),
            'slug.unique' => trans('admin.validation.unique', ['field' => trans('admin.status_categories.slug')]),
        ];
    }
}
