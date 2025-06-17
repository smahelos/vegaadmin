<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return backpack_auth()->check();
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
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
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
            'email' => __('clients.fields.email'),
            'user_id' => __('clients.fields.user_id'),
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
            'user_id.required' => __('clients.validation.user_required'),
            'user_id.exists' => __('clients.validation.user_exists'),
        ];
    }
}
