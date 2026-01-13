<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

        return $user->can('frontend.can_create_edit_status');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Determine current model id for update scenario (consistent with admin request)
        $id = $this->get('id') ?? $this->route('id') ?? $this->route('status_category');

        $rules = [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:status_categories,slug,' . ($id ?? 'NULL'),
            'description' => 'nullable|string',
        ];

        return $rules;
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

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Generate slug if not provided and name present
        if (empty($this->slug) && !empty($this->name)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
