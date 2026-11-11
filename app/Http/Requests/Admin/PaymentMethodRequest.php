<?php

namespace App\Http\Requests\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\User\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();
        if ($this->isMethod('POST')) {
            return $user?->can(Permission::PAYMENT_METHOD_CREATE) === true;
        }

        return $user?->can(Permission::PAYMENT_METHOD_UPDATE) === true;
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
