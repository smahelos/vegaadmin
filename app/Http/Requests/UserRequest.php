<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($this->user()->id),
            ],
        ];
        
        return $rules;
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => __('users.fields.name'),
            'email' => __('users.fields.email'),
        ];
    }

    /**
     * Get custom error messages for validation rules
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => __('users.validation.name_required'),
            'email.required' => __('users.validation.email_required'),
            'email.email' => __('users.validation.email_email'),
            'email.unique' => __('users.validation.email_unique'),
            'street.required' => __('users.validation.street_required'),
            'city.required' => __('users.validation.city_required'),
            'zip.required' => __('users.validation.zip_required'),
            'country.required' => __('users.validation.country_required'),
        ];
    }
}