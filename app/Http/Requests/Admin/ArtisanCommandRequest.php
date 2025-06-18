<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ArtisanCommandRequest extends FormRequest
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
            'command' => 'required|string|max:255|unique:artisan_commands,command,' . $this->id,
            'description' => 'nullable|string',
            'parameters_description' => 'nullable|string',
            'category_id' => 'required|exists:artisan_command_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('admin.artisan_commands.fields.name'),
            'command' => __('admin.artisan_commands.fields.command'),
            'description' => __('admin.artisan_commands.fields.description'),
            'parameters_description' => __('admin.artisan_commands.fields.parameters_description'),
            'category_id' => __('admin.artisan_commands.fields.category'),
            'is_active' => __('admin.artisan_commands.fields.is_active'),
            'sort_order' => __('admin.artisan_commands.fields.sort_order'),
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('admin.artisan_commands.fields.name')]),
            'command.required' => __('validation.required', ['attribute' => __('admin.artisan_commands.fields.command')]),
            'command.unique' => __('validation.unique', ['attribute' => __('admin.artisan_commands.fields.command')]),
            'category_id.required' => __('validation.required', ['attribute' => __('admin.artisan_commands.fields.category')]),
            'category_id.exists' => __('validation.exists', ['attribute' => __('admin.artisan_commands.fields.category')]),
        ];
    }
}
