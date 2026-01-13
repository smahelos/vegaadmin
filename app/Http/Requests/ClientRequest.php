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
            'name' => trans('clients.fields.name'),
            'email' => trans('clients.fields.email'),
            'phone' => trans('clients.fields.phone'),
            'street' => trans('clients.fields.street'),
            'city' => trans('clients.fields.city'),
            'zip' => trans('clients.fields.zip'),
            'country' => trans('clients.fields.country'),
            'ico' => trans('clients.fields.ico'),
            'dic' => trans('clients.fields.dic'),
            'description' => trans('clients.fields.description'),
            'shortcut' => trans('clients.fields.shortcut'),
            'is_default' => trans('clients.fields.is_default'),
        ];
    }

    /**
     * Get custom error messages for validation rules
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => __('clients.validation.name_required'),
            'email.required' => __('clients.validation.email_required'),
            'email.email' => __('clients.validation.email_valid'),
            'street.required' => __('clients.validation.street_required'),
            'city.required' => __('clients.validation.city_required'),
            'zip.required' => __('clients.validation.zip_required'),
            'country.required' => __('clients.validation.country_required'),
            'ico.regex' => __('clients.validation.ico_format'),
            'zip.regex' => __('clients.validation.zip_format'),
            'user_id.exists' => __('clients.validation.user_exists'),
        ];
    }
}
