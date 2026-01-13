<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Payment\DTO\RefundResultDTO;
use App\Http\Controllers\Frontend\SubscriptionController;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use Tests\Traits\WithSyntheticPaymentAppService;

class SubscriptionControllerFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CreatesFrontendTestEnvironment;
    use WithSyntheticPaymentAppService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpFrontendTestEnvironment();
        
        // Give user specific subscription permission
        $permission = Permission::where('name', 'frontend.can_create_subscription')
                               ->where('guard_name', 'web')
                               ->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
    }

    #[Test]
    public function index_displays_subscription_plans(): void
    {
        $activePlan = SubscriptionPlan::factory()->create([
            'is_active' => true,
            'name' => 'Active Test Plan'
        ]);
        $inactivePlan = SubscriptionPlan::factory()->create([
            'is_active' => false,
            'name' => 'Inactive Test Plan'
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('subscriptions.index', ['locale' => 'en']));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.subscriptions.index');
        $response->assertViewHas('plans');
        $response->assertSee($activePlan->name);
        $response->assertDontSee($inactivePlan->name);
    }

    #[Test]
    public function index_shows_user_subscription_when_authenticated(): void
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('subscriptions.index', ['locale' => 'en']));

        $response->assertStatus(200);
        $response->assertViewHas('userSubscription', $subscription);
    }

    #[Test]
    public function show_displays_specific_subscription_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($this->user);

    $this->bindSyntheticPaymentAppService();

        $response = $this->get(route('subscriptions.show', [
            'locale' => 'en', 
            'plan' => $plan->id
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.subscriptions.show');
        $response->assertViewHas('plan', $plan);
        $response->assertSee($plan->name);
    }

    #[Test]
    public function subscribe_requires_authentication(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->post(route('subscriptions.subscribe', [
            'locale' => 'en',
            'plan' => $plan->id
        ]));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_subscribe_to_plan(): void
    {
        // Override the default mock with success result
        $this->app->singleton(PaymentApplicationServiceInterface::class, function () {
            return new class implements PaymentApplicationServiceInterface {
                public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { 
                    return SubscriptionPaymentResultDTO::fromArray([
                        'success' => true,
                        'payment_id' => 123,
                        'status' => 'pending',
                        'redirect_url' => 'https://stub-gateway.test/pay/123'
                    ]); 
                }
                public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => false]); }
                public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => false]); }
                public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return RefundResultDTO::fromArray(['success' => false]); }
                public function getAvailableGateways(): array { return ['gopay' => ['name' => 'GoPay', 'supported_currencies' => ['CZK'], 'supports_recurring' => false]]; }
                public function cancelSubscription(Subscription $subscription): bool { return false; }
            };
        });

        $plan = SubscriptionPlan::factory()->create();
        $this->user->givePermissionTo('frontend.can_create_subscription');
        $this->actingAs($this->user);

        $response = $this->post(route('subscriptions.subscribe', [
            'locale' => 'en',
            'plan' => $plan->id
        ]), [
            'gateway' => 'gopay'
        ]);

        $response->assertRedirect();
        // Redirect should be to stub gw url
        $this->assertStringContainsString('https://stub-gateway.test/pay/', $response->headers->get('Location'));
    }

    #[Test]
    public function user_cannot_subscribe_if_already_has_active_subscription(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $existingSubscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);
        
        $this->actingAs($this->user);

        $response = $this->post(route('subscriptions.subscribe', [
            'locale' => 'en',
            'plan' => $plan->id
        ]), [
            'gateway' => 'gopay'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function cancel_requires_authentication(): void
    {
        $subscription = Subscription::factory()->create();

        $response = $this->delete(route('subscriptions.cancel', [
            'locale' => 'en',
            'subscription' => $subscription->id
        ]));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function user_can_cancel_own_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);
        
        // Give user permission to cancel their own subscription
        $this->user->givePermissionTo('frontend.can_cancel_subscription');

        $this->actingAs($this->user);

        $response = $this->delete(route('subscriptions.cancel', [
            'locale' => 'en',
            'subscription' => $subscription->id
        ]));

        $response->assertRedirect();
        $this->assertEquals('cancelled', $subscription->fresh()->status);
    }

    #[Test]
    public function user_cannot_cancel_other_users_subscription(): void
    {
        $otherUser = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'active'
        ]);
        
        $this->actingAs($this->user);

        $response = $this->delete(route('subscriptions.cancel', [
            'locale' => 'en',
            'subscription' => $subscription->id
        ]));

        $response->assertForbidden();
        $this->assertEquals('active', $subscription->fresh()->status);
    }

    #[Test]
    public function controller_uses_authorization(): void
    {
        $reflection = new \ReflectionClass(SubscriptionController::class);
        
        $this->assertTrue($reflection->hasMethod('cancel'));
        
        // Check if controller uses AuthorizesRequests trait
        $traits = $reflection->getTraitNames();
        $this->assertContains('Illuminate\Foundation\Auth\Access\AuthorizesRequests', $traits);
    }
}
