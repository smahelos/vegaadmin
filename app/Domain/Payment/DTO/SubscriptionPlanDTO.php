<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

final readonly class SubscriptionPlanDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public Money $amount,
        public string $currency,
        public string $billingPeriod,
        public int $billingInterval,
        public bool $active,
        public ?string $created,
    ) {}

    /**
     * @param  array{id: int|string, name: string, description: ?string, amount: Money, currency: string, billing_period: string, billing_interval: int|string, is_active: bool, created_at: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'],
            description: $data['description'],
            amount: $data['amount'],
            currency: $data['currency'],
            billingPeriod: $data['billing_period'],
            billingInterval: (int) $data['billing_interval'],
            active: $data['is_active'],
            created: $data['created_at'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'billing_period' => $this->billingPeriod,
            'billing_interval' => $this->billingInterval,
            'is_active' => $this->active,
            'created_at' => $this->created,
        ];
    }
}
