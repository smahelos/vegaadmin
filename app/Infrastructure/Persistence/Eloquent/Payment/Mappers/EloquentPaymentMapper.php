<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Mappers;

use App\Domain\Payment\DTO\PaymentDTO;
use App\Models\Payment;

/**
 * Maps between Eloquent PaymentMethod models and PaymentMethod DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentPaymentMapper
{
    /**
     * Convert Eloquent Payment model to PaymentDTO.
     */
    public function toDto(Payment $model): PaymentDTO
    {
        $paidAt = $model->getAttribute('paid_at');
        $expiresAt = $model->getAttribute('expires_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return PaymentDTO::fromArray(
            [
                'id' => $model->getAttribute('id'),
                'subscription_id' => $model->getAttribute('subscription_id'),
                'gateway_payment_id' => $model->getAttribute('gateway_payment_id'),
                'gateway' => $model->getAttribute('gateway'),
                'status' => $model->getAttribute('status'),
                'amount' => $model->getAttribute('amount'),
                'refunded_amount' => $model->getAttribute('refunded_amount'),
                'currency' => $model->getAttribute('currency'),
                'gateway_payment' => $model->getAttribute('gateway_payment'),
                'gateway_data' => $model->getAttribute('gateway_data'),
                'failure_reason' => $model->getAttribute('failure_reason'),
                'paid_at' => $paidAt?->toDateTimeString(),
                'expires_at' => $expiresAt?->toDateTimeString(),
                'created_at' => $createdAt?->toDateTimeString(),
                'updated_at' => $updatedAt?->toDateTimeString(),
            ],
        );
    }
}
