<?php

namespace App\Http\Requests\Admin;

class ClientRequest extends BaseEntityRequest
{
    /**
     * Get the entity type for limit checking
     */
    protected function getEntityType(): string
    {
        return 'client';
    }

    /**
     * Get required permission for client operations
     */
    protected function getRequiredPermission(): string
    {
        return 'can_create_edit_client';
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'shortcut' => 'nullable|string|max:50',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'ico' => 'nullable|string|max:20',
            'dic' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id'
        ];
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => __('clients.fields.name'),
            'shortcut' => __('clients.fields.shortcut'),
            'street' => __('clients.fields.street'),
            'city' => __('clients.fields.city'),
            'zip' => __('clients.fields.zip'),
            'country' => __('clients.fields.country'),
            'ico' => __('clients.fields.ico'),
            'dic' => __('clients.fields.dic'),
            'email' => __('clients.fields.email'),
            'phone' => __('clients.fields.phone'),
            'description' => __('clients.fields.description'),
            'user_id' => __('clients.fields.user_id'),
        ];
    }
}
