<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:payment_methods,slug,' . $this->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => __('payment_methods.fields.name'),
            'slug' => __('payment_methods.fields.slug'),
            'description' => __('payment_methods.fields.description'),
            'is_active' => __('payment_methods.fields.is_active'),
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
            'name.required' => __('payment_methods.validation.name_required'),
            'slug.required' => __('payment_methods.validation.slug_required'),
            'slug.unique' => __('payment_methods.validation.slug_unique'),
        ];
    }
}