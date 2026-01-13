<?php

namespace App\Application\Payment\DTO;

/**
 * Immutable DTO representing a refund operation result.
 */
class RefundResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $paymentId = null,
        public readonly ?float $refundedAmount = null,
        public readonly ?string $currency = null,
        public readonly ?string $status = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            paymentId: $data['payment_id'] ?? null,
            refundedAmount: $data['refunded_amount'] ?? $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            status: $data['status'] ?? null,
            error: $data['error'] ?? null,
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'payment_id' => $this->paymentId,
            'refunded_amount' => $this->refundedAmount,
            'currency' => $this->currency,
            'status' => $this->status,
            'error' => $this->error,
        ];
    }
}
