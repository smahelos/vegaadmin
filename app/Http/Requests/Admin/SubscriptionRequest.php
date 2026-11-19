<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Payment\DTO\SubscriptionWriteData;
use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Payment\ValueObjects\SubscriptionPlanId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, list<string|object>|string>
     */
    public function rules(): array
    {
        $currency = Rule::enum(Currency::class);
        $currency->only([
            Currency::CZK,
            Currency::EUR,
            Currency::USD,
        ]);

        return [
            'user_id' => 'required|integer|exists:users,id',
            'subscription_plan_id' => 'required|integer|exists:subscription_plans,id',
            'status' => [
                'required',
                Rule::enum(SubscriptionStatus::class),
            ],
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'trial_ends_at' => 'nullable|date',
            'next_billing_at' => 'nullable|date',
            // Only the plain decimals Money reads: `numeric` alone accepts `1e2`, `.50` and `+50`
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/|min:0|max:999999.99',
            'currency' => [
                'required',
                $currency,
            ],
            'metadata' => 'nullable|string|max:2000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => __('subscriptions.fields.user_id'),
            'subscription_plan_id' => __('subscriptions.fields.subscription_plan_id'),
            'status' => __('subscriptions.fields.status'),
            'starts_at' => __('subscriptions.fields.starts_at'),
            'ends_at' => __('subscriptions.fields.ends_at'),
            'trial_ends_at' => __('subscriptions.fields.trial_ends_at'),
            'next_billing_at' => __('subscriptions.fields.next_billing_at'),
            'amount' => __('subscriptions.fields.amount'),
            'currency' => __('subscriptions.fields.currency'),
            'metadata' => __('subscriptions.fields.metadata'),
        ];
    }

    public function owner(): UserId
    {
        return UserId::fromInt(
            (int) $this->validated('user_id'),
        );
    }

    public function toWriteData(): SubscriptionWriteData
    {
        $data = $this->validated();

        return new SubscriptionWriteData(
            SubscriptionPlanId::fromInt((int) $data['subscription_plan_id']),
            SubscriptionStatus::from($data['status']),
            Money::fromString(
                (string) $data['amount'],
                Currency::from($data['currency']),
            ),
            self::dateOrNull($data['starts_at'] ?? null),
            self::dateOrNull($data['ends_at'] ?? null),
            self::dateOrNull($data['trial_ends_at'] ?? null),
            self::dateOrNull($data['next_billing_at'] ?? null),
            $data['metadata'] ?? null,
        );
    }

    private static function dateOrNull(?string $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return new DateTimeImmutable($value);
    }
}
