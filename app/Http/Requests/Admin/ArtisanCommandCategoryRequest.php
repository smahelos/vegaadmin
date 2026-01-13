<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ArtisanCommandCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:artisan_command_categories,slug,' . $this->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        // For updates, ignore current record in slug uniqueness check
        if ($this->getMethod() === 'PUT' || $this->getMethod() === 'PATCH') {
            $id = $this->route('id') ?: $this->id;
            $rules['slug'] = 'required|string|max:255|unique:page_categories,slug,' . $id;
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('admin.artisan_commands.fields.name'),
            'slug' => __('admin.artisan_commands.fields.slug'),
            'description' => __('admin.artisan_commands.fields.description'),
            'is_active' => __('admin.artisan_commands.fields.is_active'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('admin.artisan_commands.validation.name_required'),
            'name.string' => __('validation.string', ['attribute' => __('admin.artisan_commands.fields.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('admin.artisan_commands.fields.name'), 'max' => 255]),
            'slug.required' => __('admin.artisan_commands.validation.slug_required'),
            'slug.string' => __('validation.string', ['attribute' => __('admin.artisan_commands.fields.slug')]),
            'slug.max' => __('validation.max.string', ['attribute' => __('admin.artisan_commands.fields.slug'), 'max' => 255]),
            'slug.unique' => __('admin.artisan_commands.validation.slug_unique'),
            'description.string' => __('validation.string', ['attribute' => __('admin.artisan_commands.fields.description')]),
            'is_active.boolean' => __('validation.boolean', ['attribute' => __('admin.artisan_commands.fields.is_active')]),
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
