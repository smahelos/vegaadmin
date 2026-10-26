<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Payment;

use App\Domain\Payment\Enums\PaymentMethodCode;

trait PaymentMethodFormFields
{
    /**
     * @return list<array<string, mixed>>
     */
    protected function getPaymentMethodFields(): array
    {
        return [
            [
                'name' => 'name',
                'label' => __('payment_methods.fields.name'),
            ],
            [
                'name' => 'slug',
                'label' => __('payment_methods.fields.slug'),
                'type' => 'select_from_array',
                'options' => $this->paymentMethodOptions(),
                'allows_null' => false,
            ],
            [
                'name' => 'country',
                'label' => __('payment_methods.fields.country'),
            ],
            [
                'name' => 'currency',
                'label' => __('payment_methods.fields.currency'),
            ],
            [
                'name' => 'icon',
                'label' => __('payment_methods.fields.icon'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function paymentMethodOptions(): array
    {
        $options = [];
        foreach (PaymentMethodCode::cases() as $code) {
            $options[$code->value] = __('payment_methods.methods.' . $code->value);
        }

        return $options;
    }
}
