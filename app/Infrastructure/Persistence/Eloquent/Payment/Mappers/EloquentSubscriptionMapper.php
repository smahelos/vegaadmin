<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Mappers;

use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\DTO\SubscriptionPlanDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\Subscription;

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
        $children = [];
        if ($model->relationLoaded('subscriptionPlan')) {
            foreach ($model->children as $child) {
                $children[] = self::toSubscriptionPlanDTO($child);
            }
        }
        return SubscriptionDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'user_id' => $model->getAttribute('user_id'),
            'subscription_plan_id' => $model->getAttribute('subscription_plan_id'),
            'subscriptionPlan' => $children,
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
     * Convert PaymentMethodDTO to array for Eloquent model creation/update.
     */
    public function fromDto(SubscriptionDTO $dto): array
    {
        return [
            'user_id' => $dto->user_id,
            'subscription_plan_id' => $dto->subscription_plan_id,
            'subscriptionPlan' => $dto->subscriptionPlan,
            'status' => $dto->status,
            'starts_at' => $dto->starts_at,
            'ends_at' => $dto->ends_at,
            'trial_ends_at' => $dto->trial_ends_at,
            'next_billing_at' => $dto->next_billing_at,
            'amount' => $dto->amount,
            'currency' => $dto->currency,
            'metadata' => $dto->metadata,
            'created_at' => $dto->created_at,
        ];
    }

    /**
     * Convert Eloquent SubscriptionPlan model to SubscriptionPlanDTO.
     */
    private static function toSubscriptionPlanDTO($model): ?SubscriptionPlanDTO
    {
        if ($model === null) {
            return null;
        }

        return SubscriptionPlanDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'description' => $model->getAttribute('description'),
            'amount' => new Money(
                amount: $model->getAttribute('amount'),
                currency: $model->getAttribute('currency')
            ),
            'currency' => $model->getAttribute('currency'),
            'billing_period' => $model->getAttribute('billing_period'),
            'billing_interval' => $model->getAttribute('billing_interval'),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
        ]);
    }
}
