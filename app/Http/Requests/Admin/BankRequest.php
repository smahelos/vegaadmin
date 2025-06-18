<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:banks,code,'.$this->id,
            'swift' => 'nullable|string|max:20',
            'country' => 'required|string|size:2',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('bank.name'),
            'code' => __('bank.code'),
            'swift' => __('bank.swift'),
            'country' => __('bank.country'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('bank.name_required'),
            'code.required' => __('bank.code_required'),
            'code.unique' => __('bank.code_unique'),
            'country.required' => __('bank.country_required'),
            'country.size' => __('bank.country_size'),
        ];
    }
}
