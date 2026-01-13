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
        // Support both route parameter and request input id for update uniqueness exception
        $id = $this->route('id') ?: $this->id; // NULL if not provided

        $rules = [
            'name' => 'required|string|min:2|max:255',
            'code' => 'required|string|min:2|max:10|unique:banks,code,' . ($id ?? 'NULL'),
            'swift' => 'nullable|string|max:20',
            'country' => 'required|string|size:2',
            'active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:1000',
        ];

        // For updates, make required fields sometimes to allow partial updates (consistent with admin request pattern)
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'sometimes|string|min:2|max:255';
            $rules['code'] = 'sometimes|string|min:2|max:10|unique:banks,code,' . ($id ?? 'NULL');
            $rules['country'] = 'sometimes|string|size:2';
        }

        return $rules;
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('bank.fields.name'),
            'code' => trans('bank.fields.code'),
            'swift' => trans('bank.fields.swift'),
            'country' => trans('bank.fields.country'),
            'active' => trans('bank.fields.is_active'),
            'description' => trans('bank.fields.description'),
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
            'name.required' => trans('bank.validation.name'),
            'name.min' => trans('validation.min.string', ['attribute' => trans('admin.banks.name'), 'min' => 2]),
            'code.required' => trans('bank.validation.code'),
            'code.unique' => trans('bank.validation.code_unique'),
            'code.min' => trans('validation.min.string', ['attribute' => trans('admin.banks.code'), 'min' => 2]),
            'country.required' => trans('bank.validation.country'),
            'country.size' => trans('bank.validation.country_size'),
        ];
    }

    /**
     * Normalize input data before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('country') && is_string($this->country)) {
            $this->merge([
                'country' => strtoupper($this->country),
            ]);
        }
    }
}
