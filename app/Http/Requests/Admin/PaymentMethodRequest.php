<?php

namespace App\Http\Requests\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use Illuminate\Validation\Rule;

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
        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                Rule::enum(PaymentMethodCode::class),
                Rule::unique('payment_methods', 'slug')->ignore($this->route('id') ?: $this->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
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
}
