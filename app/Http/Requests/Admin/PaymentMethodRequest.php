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
        if ($this->isCreatingEntity()) {
            return 'can_create_payment_method';
        }

        return 'can_update_payment_method';
    }

    public function rules(): array
    {
        $slugUnique = Rule::unique('payment_methods', 'slug');
        $slugUnique->ignore(
            $this->route('id') ?: $this->id,
        );

        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                Rule::enum(PaymentMethodCode::class),
                $slugUnique,
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
