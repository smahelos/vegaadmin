<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class PaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            // Slug can be nullable (auto-generated if empty), unique within payment_methods
            'slug' => 'nullable|string|max:255|unique:payment_methods,slug,' . $this->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('id') ?: $this->id;
            $rules['slug'] = 'sometimes|string|max:255|unique:payment_methods,slug,' . $id;
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
    public function messages(): array
    {
        return [
            'name.required' => __('payment_methods.validation.name_required'),
            'slug.unique' => __('payment_methods.validation.slug_unique'),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug only if empty and name present
        if (!$this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }
}
