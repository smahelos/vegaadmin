<?php

namespace App\Application\Payment\DTO;

use App\Models\Payment;

/**
 * Immutable DTO representing the result of initiating or verifying a subscription payment.
 */
class SubscriptionPaymentResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $paymentId = null,
        public readonly ?string $status = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            paymentId: $data['payment_id'] ?? null,
            status: $data['status'] ?? null,
            redirectUrl: $data['payment_url'] ?? $data['redirect_url'] ?? null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            error: $data['error'] ?? null,
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'payment_id' => $this->paymentId,
            'status' => $this->status,
            'redirect_url' => $this->redirectUrl,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'error' => $this->error,
        ];
    }
}
