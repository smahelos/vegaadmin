<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Payment\Mappers;

use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\DTO\SubscriptionPlanDTO;
use App\Domain\Payment\DTO\SubscriptionWriteData;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

/**
 * Maps between Eloquent Subscription models and Subscription DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
final class EloquentSubscriptionMapper
{
    /**
     * Convert Eloquent Subscription model to SubscriptionDTO.
     */
    public function toDto(Subscription $model): SubscriptionDTO
    {
        $plan = $model->relationLoaded('subscriptionPlan') ? $model->subscriptionPlan : null;

        $startsAt = $model->getAttribute('starts_at');
        $endsAt = $model->getAttribute('ends_at');
        $trialEndsAt = $model->getAttribute('trial_ends_at');
        $nextBillingAt = $model->getAttribute('next_billing_at');
        $createdAt = $model->getAttribute('created_at');

        return SubscriptionDTO::fromArray(
            [
                'id' => $model->getAttribute('id'),
                'user_id' => $model->getAttribute('user_id'),
                'subscription_plan_id' => $model->getAttribute('subscription_plan_id'),
                'subscriptionPlan' => $plan === null ? null : self::subscriptionPlanData($plan),
                'status' => $model->getAttribute('status'),
                'starts_at' => $startsAt?->toDateTimeString(),
                'ends_at' => $endsAt?->toDateTimeString(),
                'trial_ends_at' => $trialEndsAt?->toDateTimeString(),
                'next_billing_at' => $nextBillingAt?->toDateTimeString(),
                'amount' => $model->getAttribute('amount'),
                'currency' => $model->getAttribute('currency'),
                'created_at' => $createdAt?->toDateTimeString(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(SubscriptionWriteData $data): array
    {
        return [
            'subscription_plan_id' => $data->planId->value,
            'status' => $data->status->value,
            'amount' => $data->amount->amount,
            'currency' => $data->amount->currency,
            'starts_at' => $data->starts,
            'ends_at' => $data->ends,
            'trial_ends_at' => $data->trialEnds,
            'next_billing_at' => $data->nextBilling,
            'metadata' => $data->metadata,
        ];
    }

    public function toPlanDto(SubscriptionPlan $plan): SubscriptionPlanDTO
    {
        return SubscriptionPlanDTO::fromArray(
            self::subscriptionPlanData($plan),
        );
    }

    /**
     * The plan's data in the shape SubscriptionPlanDTO::fromArray() reads.
     *
     * @return array<string, mixed>
     */
    private static function subscriptionPlanData(SubscriptionPlan $plan): array
    {
        $currency = $plan->getAttribute('currency');
        $createdAt = $plan->getAttribute('created_at');

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
            'created_at' => $createdAt?->toDateTimeString(),
        ];
    }
}
