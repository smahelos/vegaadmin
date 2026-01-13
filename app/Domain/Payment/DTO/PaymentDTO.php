<?php

namespace App\Domain\Payment\DTO;

class PaymentDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $subscription_id,
        public readonly ?int $gateway_payment_id,
        public readonly ?string $gateway,
        public readonly ?string $status,
        public readonly ?string $amount,
        public readonly ?string $refunded_amount,
        public readonly ?string $currency,
        public readonly ?string $gateway_payment,
        public readonly ?string $gateway_data,
        public readonly ?string $failure_reason,
        public readonly ?string $original_status_enum_note,
        public readonly ?string $paid_at,
        public readonly ?string $expires_at,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            subscription_id: (int) $data['subscription_id'],
            gateway_payment_id: isset($data['gateway_payment_id']) ? (int)$data['gateway_payment_id'] : null,
            gateway: $data['gateway'] ?? null,
            status: $data['status'] ?? null,
            amount: $data['amount'] ?? null,
            refunded_amount: $data['refunded_amount'] ?? null,
            currency: $data['currency'] ?? null,
            gateway_payment: $data['gateway_payment'] ?? null,
            gateway_data: $data['gateway_data'] ?? null,
            failure_reason: $data['failure_reason'] ?? null,
            original_status_enum_note: $data['original_status_enum_note'] ?? null,
            paid_at: $data['paid_at'] ?? null,
            expires_at: $data['expires_at'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'gateway_payment_id' => $this->gateway_payment_id,
            'gateway' => $this->gateway,
            'status' => $this->status,
            'amount' => $this->amount,
            'refunded_amount' => $this->refunded_amount,
            'currency' => $this->currency,
            'gateway_payment' => $this->gateway_payment,
            'gateway_data' => $this->gateway_data,
            'failure_reason' => $this->failure_reason,
            'original_status_enum_note' => $this->original_status_enum_note,
            'paid_at' => $this->paid_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
