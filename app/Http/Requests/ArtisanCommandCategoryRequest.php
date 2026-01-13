<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ArtisanCommandCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // only allow updates if the user is logged in
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        // Determine current entity ID (route param or provided id input) for uniqueness exception
        $id = $this->route('id') ?? $this->get('id');
        $id = $id ?: 'NULL';

        return [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:artisan_command_categories,slug,' . $id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('admin.artisan_commands.fields.name'),
            'slug' => trans('admin.artisan_commands.fields.slug'),
            'description' => trans('admin.artisan_commands.fields.description'),
            'is_active' => trans('admin.artisan_commands.fields.is_active'),
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('admin.validation.required', ['field' => trans('admin.artisan_commands.fields.name')]),
            'name.min' => trans('admin.validation.min', ['field' => trans('admin.artisan_commands.fields.name'), 'min' => 2]),
            'name.max' => trans('admin.validation.max', ['field' => trans('admin.artisan_commands.fields.name'), 'max' => 255]),
            'slug.required' => trans('admin.validation.required', ['field' => trans('admin.artisan_commands.fields.slug')]),
            'slug.min' => trans('admin.validation.min', ['field' => trans('admin.artisan_commands.fields.slug'), 'min' => 2]),
            'slug.max' => trans('admin.validation.max', ['field' => trans('admin.artisan_commands.fields.slug'), 'max' => 255]),
            'slug.unique' => trans('admin.validation.unique', ['field' => trans('admin.artisan_commands.fields.slug')]),
            'description.max' => trans('admin.validation.max', ['field' => trans('admin.artisan_commands.fields.description'), 'max' => 1000]),
            'is_active.boolean' => trans('admin.validation.boolean', ['field' => trans('admin.artisan_commands.fields.is_active')]),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
