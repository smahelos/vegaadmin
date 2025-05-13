<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TaxRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ];
    }

    public function attributes()
    {
        return [
            'name' => __('tax.name'),
            'rate' => __('tax.rate'),
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('tax.name_required'),
            'rate.required' => __('tax.rate_required'),
            'rate.numeric' => __('tax.rate_numeric'),
            'rate.min' => __('tax.rate_min'),
        ];
    }
}
