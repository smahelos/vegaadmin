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
            'name' => __('artisan_commands.fields.name'),
            'command' => __('artisan_commands.fields.command'),
            'description' => __('artisan_commands.fields.description'),
            'parameters_description' => __('artisan_commands.fields.parameters_description'),
            'category_id' => __('artisan_commands.fields.category_id'),
            'is_active' => __('artisan_commands.fields.is_active'),
            'sort_order' => __('artisan_commands.fields.sort_order'),
        ];
    }
}
