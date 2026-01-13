<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        // For profile routes (frontend), any authenticated user can edit their own profile
        // For admin routes, we would check the 'frontend.can_create_edit_user' permission
        $route = $this->route();
        if ($route && str_contains($route->getName() ?? '', 'frontend.profile')) {
            return true; // Authenticated user can edit own profile
        }

        // For other user management operations, check permission
        $user = Auth::user();
        return $user->can('frontend.can_create_edit_user');
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;
        $isCreate = $this->isCreateOperation();
        $isProfileUpdate = $this->route() && str_contains($this->route()->getName() ?? '', 'frontend.profile');

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => ['nullable','string','regex:/^\+?[0-9()\s-]{3,25}$/'],
            'ico' => 'nullable|string|max:20',
            'dic' => 'nullable|string|max:30',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
        ];

        // For profile updates, address fields are optional (only name/email in form)
        // For other operations (admin CRUD), they are required
        if ($isProfileUpdate) {
            $rules['street'] = 'nullable|string|max:255';
            $rules['city'] = 'nullable|string|max:255';
            $rules['zip'] = 'nullable|string|max:20';
            $rules['country'] = 'nullable|string|max:100';
        } else {
            $rules['street'] = 'required|string|max:255';
            $rules['city'] = 'required|string|max:255';
            $rules['zip'] = 'required|string|max:20';
            $rules['country'] = 'required|string|max:100';
        }

        if ($isCreate) {
            $rules['password'] = 'required|min:7|confirmed';
            $rules['password_confirmation'] = 'required';
        } else {
            $rules['password'] = 'nullable|min:7|confirmed';
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
            'street' => __('users.fields.street'),
            'city' => __('users.fields.city'),
            'zip' => __('users.fields.zip'),
            'country' => __('users.fields.country'),
            'phone' => __('users.fields.phone'),
            'ico' => __('users.fields.ico'),
            'dic' => __('users.fields.dic'),
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
            'street.required' => __('users.validation.street_required'),
            'city.required' => __('users.validation.city_required'),
            'zip.required' => __('users.validation.zip_required'),
            'country.required' => __('users.validation.country_required'),
            'phone.regex' => __('users.validation.phone_format'),
        ];
    }

    /**
     * Determine if request is create operation (POST)
     */
    public function isCreateOperation(): bool
    {
        return $this->method() === 'POST';
    }
}
