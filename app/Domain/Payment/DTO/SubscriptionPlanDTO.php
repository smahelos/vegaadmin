<?php

namespace App\Domain\Payment\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

class SubscriptionPlanDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $name,
        public readonly ?string $description,
        public readonly Money $amount,
        public readonly ?string $currency,
        public readonly ?string $billing_period,
        public readonly ?string $billing_interval,
        public readonly bool $is_active,
        public readonly ?string $created_at,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (int) $data['name'],
            description: $data['description'] ?? null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            billing_period: $data['billing_period'] ?? null,
            billing_interval: $data['billing_interval'] ?? null,
            is_active: (bool) ($data['is_active'] ?? false),
            created_at: $data['created_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period,
            'billing_interval' => $this->billing_interval,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
