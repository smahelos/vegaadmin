<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Payment\Mappers;

use App\Domain\Shared\Money\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentSubscriptionMapper;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EloquentSubscriptionMapperTest extends TestCase
{
    #[Test]
    public function maps_loaded_plan_with_its_price(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $plan = $subscriptionPlanFactory->create(
            [
                'name' => 'Basic',
                'price' => '50.00',
                'currency' => 'CZK',
            ],
        );
        $subscriptionFactory = Subscription::factory();
        $subscription = $subscriptionFactory->create(['subscription_plan_id' => $plan->id]);

        $dto = (new EloquentSubscriptionMapper)->toDto(
            $subscription->load('subscriptionPlan'),
        );

        $this->assertSame('Basic', $dto->subscriptionPlan->name);
        $this->assertTrue(
            $dto->subscriptionPlan->amount->equals(
                Money::fromString('50.00', 'CZK'),
            ),
        );
    }

    #[Test]
    public function maps_subscription_without_loaded_plan(): void
    {
        $subscriptionFactory = Subscription::factory();
        $subscription = $subscriptionFactory->create();

        $dto = (new EloquentSubscriptionMapper)->toDto($subscription);

        $this->assertNull($dto->subscriptionPlan);
    }
}
