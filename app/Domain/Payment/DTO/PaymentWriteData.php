<?php

namespace App\Domain\Payment\DTO;

/**
 * Immutable write data for Payment mutations.
 * All fields are optional to support partial updates; null means "do not change".
 */
class PaymentWriteData
{
    /**
     * @param int|null $subscription_id
     * @param int|null $gateway_payment_id
     * @param string|null $gateway
     * @param string|null $status
     * @param string|null $amount
     * @param string|null $refunded_amount
     * @param string|null $currency
     * @param string|null $gateway_payment
     * @param string|null $gateway_data
     * @param string|null $failure_reason
     * @param string|null $original_status_enum_note
     * @param string|null $paid_at
     * @param string|null $expires_at
     * @param string|null $created_at
     * @param string|null $updated_at
     */
    public function __construct(
        public readonly ?int $subscription_id = null,
        public readonly ?int $gateway_payment_id = null,
        public readonly ?string $gateway = null,
        public readonly ?string $status = null,
        public readonly ?string $amount = null,
        public readonly ?string $refunded_amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $gateway_payment = null,
        public readonly ?string $gateway_data = null,
        public readonly ?string $failure_reason = null,
        public readonly ?string $original_status_enum_note = null,
        public readonly ?string $paid_at = null,
        public readonly ?string $expires_at = null,
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
    ) {}

    /**
     * Create from associative array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            subscription_id: isset($data['subscription_id']) ? (int)$data['subscription_id'] : null,
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

    /**
     * Convert to associative array for infrastructure layer usage.
     * Filters out null values to avoid overwriting existing data with null during updates.
     */
    public function toArray(): array
    {
        $properties = get_object_vars($this);
        return array_filter($properties, fn($value) => $value !== null);
    }

    public function toModelAttributes(): array
    {
        $map = [];
        if ($this->subscription_id !== null) { $map['subscription_id'] = $this->subscription_id; }
        if ($this->gateway_payment_id !== null) { $map['gateway_payment_id'] = $this->gateway_payment_id; }
        if ($this->gateway !== null) { $map['gateway'] = $this->gateway; }
        if ($this->status !== null) { $map['status'] = $this->status; }
        if ($this->amount !== null) { $map['amount'] = $this->amount; }
        if ($this->refunded_amount !== null) { $map['refunded_amount'] = $this->refunded_amount; }
        if ($this->currency !== null) { $map['currency'] = $this->currency; }
        if ($this->gateway_payment !== null) { $map['gateway_payment'] = $this->gateway_payment; }
        if ($this->gateway_data !== null) { $map['gateway_data'] = $this->gateway_data; }
        if ($this->failure_reason !== null) { $map['failure_reason'] = $this->failure_reason; }
        if ($this->original_status_enum_note !== null) { $map['original_status_enum_note'] = $this->original_status_enum_note; }
        if ($this->paid_at !== null) { $map['paid_at'] = $this->paid_at; }
        if ($this->expires_at !== null) { $map['expires_at'] = $this->expires_at; }
        if ($this->created_at !== null) { $map['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $map['updated_at'] = $this->updated_at; }
        return $map;
    }
}
