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
        // Only authenticated backpack users with command management permission
        return backpack_auth()->check() && backpack_auth()->user()->can('can_create_edit_command');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->get('id') ?? $this->route('id') ?? $this->route('artisan_command');

        return [
            'name' => 'required|string|min:2|max:255',
            'command' => 'required|string|min:2|max:255|unique:artisan_commands,command,' . ($id ?? 'NULL'),
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
            'name.string' => __('validation.string', ['attribute' => __('admin.artisan_commands.fields.name')]),
            'name.min' => __('validation.min.string', ['attribute' => __('admin.artisan_commands.fields.name'), 'min' => 2]),
            'name.max' => __('validation.max.string', ['attribute' => __('admin.artisan_commands.fields.name'), 'max' => 255]),
            'command.required' => __('validation.required', ['attribute' => __('admin.artisan_commands.fields.command')]),
            'command.string' => __('validation.string', ['attribute' => __('admin.artisan_commands.fields.command')]),
            'command.min' => __('validation.min.string', ['attribute' => __('admin.artisan_commands.fields.command'), 'min' => 2]),
            'command.max' => __('validation.max.string', ['attribute' => __('admin.artisan_commands.fields.command'), 'max' => 255]),
            'command.unique' => __('validation.unique', ['attribute' => __('admin.artisan_commands.fields.command')]),
            'description.string' => __('validation.string', ['attribute' => __('admin.artisan_commands.fields.description')]),
            'parameters_description.string' => __('validation.string', ['attribute' => __('admin.artisan_commands.fields.parameters_description')]),
            'category_id.required' => __('validation.required', ['attribute' => __('admin.artisan_commands.fields.category')]),
            'category_id.exists' => __('validation.exists', ['attribute' => __('admin.artisan_commands.fields.category')]),
            'is_active.boolean' => __('validation.boolean', ['attribute' => __('admin.artisan_commands.fields.is_active')]),
            'sort_order.integer' => __('validation.integer', ['attribute' => __('admin.artisan_commands.fields.sort_order')]),
            'sort_order.min' => __('validation.min.numeric', ['attribute' => __('admin.artisan_commands.fields.sort_order'), 'min' => 0]),
        ];
    }
}
