<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Mappers;

use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\DTO\SubscriptionPlanDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

/**
 * Maps between Eloquent Subscription models and Subscription DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentSubscriptionMapper
{
    /**
     * Convert Eloquent Subscription model to SubscriptionDTO.
     */
    public function toDto(Subscription $model): SubscriptionDTO
    {
        $plan = $model->relationLoaded('subscriptionPlan') ? $model->subscriptionPlan : null;

        return SubscriptionDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'user_id' => $model->getAttribute('user_id'),
            'subscription_plan_id' => $model->getAttribute('subscription_plan_id'),
            'subscriptionPlan' => $plan === null ? null : self::subscriptionPlanData($plan),
            'status' => $model->getAttribute('status'),
            'starts_at' => $model->getAttribute('starts_at')?->toDateTimeString(),
            'ends_at' => $model->getAttribute('ends_at')?->toDateTimeString(),
            'trial_ends_at' => $model->getAttribute('trial_ends_at')?->toDateTimeString(),
            'next_billing_at' => $model->getAttribute('next_billing_at')?->toDateTimeString(),
            'amount' => $model->getAttribute('amount'),
            'currency' => $model->getAttribute('currency'),
            'metadata' => $model->getAttribute('metadata'),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
        ]);
    }

    /**
     * The plan's data in the shape SubscriptionPlanDTO::fromArray() reads.
     *
     * @return array<string, mixed>
     */
    private static function subscriptionPlanData(SubscriptionPlan $plan): array
    {
        $currency = $plan->getAttribute('currency');

        return [
            'id' => $plan->getAttribute('id'),
            'name' => $plan->getAttribute('name'),
            'description' => $plan->getAttribute('description'),
            'amount' => Money::fromString(
                (string) $plan->getAttribute('price'),
                $currency,
            ),
            'currency' => $currency,
            'billing_period' => $plan->getAttribute('billing_period'),
            'billing_interval' => $plan->getAttribute('billing_interval'),
            'is_active' => (bool) $plan->getAttribute('is_active'),
            'created_at' => $plan->getAttribute('created_at')?->toDateTimeString(),
        ];
    }
}
