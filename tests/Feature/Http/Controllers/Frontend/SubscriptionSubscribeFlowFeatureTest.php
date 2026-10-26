<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionSubscribeFlowFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Stub application service to return deterministic redirect URL
        $this->app->bind(PaymentApplicationServiceInterface::class, function () {
            return new class implements PaymentApplicationServiceInterface
            {
                public function initiateSubscription(
                    Subscription $subscription,
                    string $gateway,
                    array $data = [],
                ): SubscriptionPaymentResultDTO {
                    return new SubscriptionPaymentResultDTO(
                        success: true,
                        paymentId: 123,
                        status: 'pending',
                        redirectUrl: 'https://pay.example.test/order/123',
                        amount: 50.00,
                        currency: 'EUR',
                        error: null,
                    );
                }

                public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
                {
                    return new SubscriptionPaymentResultDTO(
                        success: true,
                        status: 'pending',
                    );
                }

                public function getAvailableGateways(): array
                {
                    return ['gopay'];
                }

                public function cancelSubscription(Subscription $subscription): bool
                {
                    return true;
                }
            };
        });
    }

    #[Test]
    public function subscribe_endpoint_redirects_to_gateway_url(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price' => 50.00,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('subscriptions.subscribe', [
            'locale' => 'cs',
            'plan' => $plan->id,
        ]), [
            'gateway' => 'gopay',
        ]);

        $response->assertRedirect('https://pay.example.test/order/123');

        // Subscription should be persisted (pending) before redirect
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function duplicate_active_subscription_is_blocked(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price' => 50.00,
            'currency' => 'EUR',
            'is_active' => true,
        ]);
        // Existing active subscription
        $user->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'amount' => $plan->price,
            'currency' => $plan->currency,
        ]);

        $this->actingAs($user);
        $response = $this->post(route('subscriptions.subscribe', [
            'locale' => 'cs',
            'plan' => $plan->id,
        ]), [
            'gateway' => 'gopay',
        ]);

        $response->assertRedirect(route('subscriptions.index', ['locale' => 'cs']));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function invalid_gateway_fails_validation(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->post(route('subscriptions.subscribe', [
            'locale' => 'cs',
            'plan' => $plan->id,
        ]), [
            'gateway' => 'unknown',
        ]);

        $response->assertSessionHasErrors('gateway');
        $this->assertDatabaseMissing('subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
        ]);
    }

    #[Test]
    public function cancel_subscription_flow_sets_cancelled_status(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        // Create subscription (pending) through subscribe endpoint
        $this->post(route('subscriptions.subscribe', [
            'locale' => 'cs',
            'plan' => $plan->id,
        ]), ['gateway' => 'gopay']);

        $subscription = $user->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('pending', $subscription->status);

        // Simulate user activation (to allow cancel business path) – mimic lifecycle
        $subscription->status = 'active';
        $subscription->save();

        // Bypass policy authorization for test scope; focus on cancellation behavior.
        Gate::before(function () {
            return true;
        });

        $response = $this->delete(route('subscriptions.cancel', [
            'locale' => 'cs',
            'subscription' => $subscription->id,
        ]));

        $response->assertRedirect(route('subscriptions.index', ['locale' => 'cs']));
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'cancelled',
        ]);
    }
}
