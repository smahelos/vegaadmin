<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // only allow updates if the user is logged in
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $id = $this->get('id') ?? 'NULL';
        
        return [
            'name' => 'required|string|min:2|max:255',
            'code' => 'required|string|min:2|max:10|unique:banks,code,' . $id,
            'swift' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('banks.name'),
            'code' => trans('banks.code'),
            'swift' => trans('banks.swift'),
            'country' => trans('banks.country'),
            'active' => trans('banks.active'),
            'description' => trans('banks.description'),
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('banks.validation.name_required'),
            'name.min' => trans('banks.validation.name_min'),
            'code.required' => trans('banks.validation.code_required'),
            'code.unique' => trans('banks.validation.code_unique'),
            'country.required' => trans('banks.validation.country_required'),
        ];
    }
}
