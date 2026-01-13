<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;

class PaymentMethodRequest extends BaseEntityRequest
{
    protected function getEntityType(): string
    {
        return 'payment_method';
    }

    protected function getRequiredPermission(): string
    {
        return 'can_create_edit_payment_method';
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
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

    public function attributes(): array
    {
        return [
            'name' => __('payment_methods.fields.name'),
            'slug' => __('payment_methods.fields.slug'),
            'description' => __('payment_methods.fields.description'),
            'is_active' => __('payment_methods.fields.is_active'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('payment_methods.validation.name_required'),
            'slug.unique' => __('payment_methods.validation.slug_unique'),
        ];
    }

    public function prepareForValidation(): void
    {
        if (empty($this->slug) && !empty($this->name)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
