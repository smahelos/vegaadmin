<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:artisan_command_categories,slug,' . $this->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
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
}
