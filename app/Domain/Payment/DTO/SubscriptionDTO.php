<?php

namespace App\Domain\Payment\DTO;

use App\Domain\Payment\DTO\SubscriptionPlanDTO;

class SubscriptionDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly ?int $subscription_plan_id,
        public readonly ?SubscriptionPlanDTO $subscriptionPlan,
        public readonly ?string $status,
        public readonly ?string $starts_at,
        public readonly ?string $ends_at,
        public readonly ?string $trial_ends_at,
        public readonly ?string $next_billing_at,
        public readonly ?string $amount,
        public readonly ?string $currency,
        public readonly ?string $metadata,
        public readonly ?string $created_at,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            user_id: (int) $data['user_id'],
            subscription_plan_id: isset($data['subscription_plan_id']) ? (int)$data['subscription_plan_id'] : null,
            subscriptionPlan: isset($data['subscriptionPlan']) ? SubscriptionPlanDTO::fromArray($data['subscriptionPlan']) : null,
            status: $data['status'] ?? null,
            starts_at: $data['starts_at'] ?? null,
            ends_at: $data['ends_at'] ?? null,
            trial_ends_at: $data['trial_ends_at'] ?? null,
            next_billing_at: $data['next_billing_at'] ?? null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            metadata: $data['metadata'] ?? null,
            created_at: $data['created_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'subscription_plan_id' => $this->subscription_plan_id,
            'subscriptionPlan' => $this->subscriptionPlan ? $this->subscriptionPlan->toArray() : null,
            'status' => $this->status,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'trial_ends_at' => $this->trial_ends_at,
            'next_billing_at' => $this->next_billing_at,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
