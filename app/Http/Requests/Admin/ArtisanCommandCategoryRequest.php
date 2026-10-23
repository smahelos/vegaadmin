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
            $rules['slug'] = 'required|string|max:255|unique:artisan_command_categories,slug,' . $id;
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('artisan_commands.categories.fields.name'),
            'slug' => __('artisan_commands.categories.fields.slug'),
            'description' => __('artisan_commands.categories.fields.description'),
            'is_active' => __('artisan_commands.categories.fields.is_active'),
        ];
    }

    /**
     * Prepare the data for validation.
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
