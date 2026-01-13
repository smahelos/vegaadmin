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
        return PaymentDTO::fromArray([
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
            'original_status_enum_note' => $model->getAttribute('original_status_enum_note'),
            'paid_at' => $model->getAttribute('paid_at')?->toDateTimeString(),
            'expires_at' => $model->getAttribute('expires_at')?->toDateTimeString(),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
            'updated_at' => $model->getAttribute('updated_at')?->toDateTimeString(),
        ]);
    }

    /**
     * Convert PaymentMethodDTO to array for Eloquent model creation/update.
     */
    public function fromDto(PaymentDTO $dto): array
    {
        return [
            'subscription_id' => $dto->subscription_id,
            'gateway_payment_id' => $dto->gateway_payment_id,
            'gateway' => $dto->gateway,
            'status' => $dto->status,
            'amount' => $dto->amount,
            'refunded_amount' => $dto->refunded_amount,
            'currency' => $dto->currency,
            'gateway_payment' => $dto->gateway_payment,
            'gateway_data' => $dto->gateway_data,
            'failure_reason' => $dto->failure_reason,
            'original_status_enum_note' => $dto->original_status_enum_note,
            'paid_at' => $dto->paid_at,
            'expires_at' => $dto->expires_at,
            'created_at' => $dto->created_at,
            'updated_at' => $dto->updated_at,
        ];
    }
}
