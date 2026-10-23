<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        return 'frontend.can_create_edit_client';
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'client_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'shortcut' => 'nullable|string|max:50',
            'phone' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'ico' => 'nullable|string|max:20',
            'dic' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ];

        // Email is always required
        $rules['email'] = [
            'required',
            'email',
        ];

        return $rules;
    }

    /**
     * Get the validation attributes for error messages
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => __('clients.fields.name'),
            'email' => __('clients.fields.email'),
            'phone' => __('clients.fields.phone'),
            'street' => __('clients.fields.street'),
            'city' => __('clients.fields.city'),
            'zip' => __('clients.fields.zip'),
            'country' => __('clients.fields.country'),
            'ico' => __('clients.fields.ico'),
            'dic' => __('clients.fields.dic'),
            'description' => __('clients.fields.description'),
            'shortcut' => __('clients.fields.shortcut'),
            'is_default' => __('clients.fields.is_default'),
        ];
    }
}
