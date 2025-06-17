<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $id = $this->get('id') ?? $this->route('id');
        $isCreateOperation = $this->isCreateOperation();
        
        $rules = [
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|integer',
            'ico' => 'nullable|string|max:20',
            'dic' => 'nullable|string|max:30',
        ];
        
        // Email validation with unique check
        $rules['email'] = [
            'required',
            'email',
            Rule::unique('users', 'email')->ignore($id),
        ];
        
        // Password required only for create operations
        if ($isCreateOperation) {
            $rules['password'] = 'required|min:8|confirmed';
            $rules['password_confirmation'] = 'required';
        } else {
            $rules['password'] = 'nullable|min:8|confirmed';
            $rules['password_confirmation'] = 'nullable|required_with:password';
        }
        
        return $rules;
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => __('users.fields.name'),
            'email' => __('users.fields.email'),
            'password' => __('users.fields.password'),
            'password_confirmation' => __('users.fields.password_confirmation'),
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
            'name.required' => __('users.validation.name_required'),
            'email.required' => __('users.validation.email_required'),
            'email.email' => __('users.validation.email_email'),
            'email.unique' => __('users.validation.email_unique'),
            'password.required' => __('users.validation.password_required'),
            'password.min' => __('users.validation.password_min'),
            'password.confirmed' => __('users.validation.password_confirmed'),
            'password_confirmation.required' => __('users.validation.password_confirmation_required'),
            'password_confirmation.required_with' => __('users.validation.password_confirmation_required'),
        ];
    }

    /**
     * Check if request is for creating a new record
     * 
     * @return bool
     */
    public function isCreateOperation(): bool
    {
        return $this->method() === 'POST';
    }
}
