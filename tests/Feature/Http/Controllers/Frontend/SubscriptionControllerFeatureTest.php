<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Domain\User\Enums\Permission;
use App\Http\Controllers\Frontend\SubscriptionController;
use App\Models\Permission as PermissionModel;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\Payment\RecordingPaymentApplicationService;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use Tests\Traits\WithSyntheticPaymentAppService;

class SubscriptionControllerFeatureTest extends TestCase
{
    use CreatesFrontendTestEnvironment;
    use WithSyntheticPaymentAppService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();

        // Give user specific subscription permission
        $permissionQuery = PermissionModel::where('name', Permission::SUBSCRIPTION_CREATE->value);
        $permissionQuery->where('guard_name', 'web');
        $permission = $permissionQuery->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }
    }

    #[Test]
    public function index_displays_subscription_plans(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $activePlan = $subscriptionPlanFactory->create([
            'is_active' => true,
            'name' => 'Active Test Plan',
        ]);
        $inactivePlan = $subscriptionPlanFactory->create([
            'is_active' => false,
            'name' => 'Inactive Test Plan',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(
            route('subscriptions.index', ['locale' => 'en']),
        );

        $response->assertStatus(200);
        $response->assertViewIs('frontend.subscriptions.index');
        $response->assertViewHas('plans');
        $response->assertSee($activePlan->name);
        $response->assertDontSee($inactivePlan->name);
    }

    #[Test]
    public function index_shows_user_subscription_when_authenticated(): void
    {
        $subscriptionFactory = Subscription::factory();
        $subscription = $subscriptionFactory->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(
            route('subscriptions.index', ['locale' => 'en']),
        );

        $response->assertStatus(200);
        $response->assertViewHas('userSubscription', $subscription);
    }

    #[Test]
    public function my_subscription_shows_the_active_subscription(): void
    {
        $subscriptionFactory = Subscription::factory();
        $now = CarbonImmutable::now();
        $subscription = $subscriptionFactory->create(
            [
                'user_id' => $this->user->id,
                'status' => 'active',
                'trial_ends_at' => $now->addDays(3),
                'ends_at' => $now->addDays(10),
            ],
        );

        $this->actingAs($this->user);

        $response = $this->get(
            route('subscriptions.my-subscription', ['locale' => 'en']),
        );

        $response->assertOk();
        $response->assertViewIs('frontend.subscriptions.my-subscription');
        $response->assertSee($subscription->subscriptionPlan->name);
    }

    #[Test]
    public function show_displays_specific_subscription_plan(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $plan = $subscriptionPlanFactory->create();

        $this->actingAs($this->user);

        $this->bindSyntheticPaymentAppService();

        $response = $this->get(
            route('subscriptions.show', [
                'locale' => 'en',
                'plan' => $plan->id,
            ]),
        );

        $response->assertStatus(200);
        $response->assertViewIs('frontend.subscriptions.show');
        $response->assertViewHas('plan', $plan);
        $response->assertSee($plan->name);
    }

    #[Test]
    public function subscribe_requires_authentication(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $plan = $subscriptionPlanFactory->create();

        $response = $this->post(
            route('subscriptions.subscribe', [
                'locale' => 'en',
                'plan' => $plan->id,
            ]),
        );

        $response->assertRedirect(
            route('login'),
        );
    }

    #[Test]
    public function user_cannot_subscribe_if_already_has_active_subscription(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $plan = $subscriptionPlanFactory->create();
        $subscriptionFactory = Subscription::factory();
        $existingSubscription = $subscriptionFactory->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $response = $this->post(
            route('subscriptions.subscribe', [
                'locale' => 'en',
                'plan' => $plan->id,
            ]),
            [
                'gateway' => 'gopay',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function subscribe_sends_the_payment_routes_to_the_gateway_in_production(): void
    {
        config(['services.gopay.production' => true]);

        $initiated = $this->subscribeThroughRecordingPaymentApp();

        $this->assertSame(
            route('payment.return'),
            $initiated['return_url'],
        );
        $this->assertSame(
            route('payment.notify'),
            $initiated['notify_url'],
        );
    }

    #[Test]
    public function subscribe_sends_the_configured_test_urls_outside_production(): void
    {
        config(
            [
                'services.gopay.production' => false,
                'services.gopay.test_return_url' => 'https://return.test/a',
                'services.gopay.test_notify_url' => 'https://notify.test/b',
            ],
        );

        $initiated = $this->subscribeThroughRecordingPaymentApp();

        $this->assertSame('https://return.test/a', $initiated['return_url']);
        $this->assertSame('https://notify.test/b', $initiated['notify_url']);
    }

    /**
     * An unset variable is null, an empty one from the `.env.example` template is an empty string.
     *
     * @return array<string, array{?string}>
     */
    public static function unsetTestUrls(): array
    {
        return [
            'unset' => [null],
            'empty' => [''],
        ];
    }

    #[Test]
    #[DataProvider('unsetTestUrls')]
    public function subscribe_sends_the_payment_routes_outside_production_without_test_urls(?string $testUrl): void
    {
        config(
            [
                'services.gopay.production' => false,
                'services.gopay.test_return_url' => $testUrl,
                'services.gopay.test_notify_url' => $testUrl,
            ],
        );

        $initiated = $this->subscribeThroughRecordingPaymentApp();

        $this->assertSame(
            route('payment.return'),
            $initiated['return_url'],
        );
        $this->assertSame(
            route('payment.notify'),
            $initiated['notify_url'],
        );
    }

    #[Test]
    public function cancel_requires_authentication(): void
    {
        $subscriptionFactory = Subscription::factory();
        $subscription = $subscriptionFactory->create();

        $response = $this->delete(
            route(
                'subscriptions.cancel',
                [
                    'locale' => 'en',
                    'subscription' => $subscription->id,
                ],
            ),
        );

        $response->assertRedirect(
            route('login'),
        );
    }

    #[Test]
    public function user_can_cancel_own_subscription(): void
    {
        $subscriptionFactory = Subscription::factory();
        $subscription = $subscriptionFactory->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        // Give user permission to cancel their own subscription
        $this->user->givePermissionTo(Permission::SUBSCRIPTION_CANCEL);

        $this->actingAs($this->user);

        $response = $this->delete(
            route(
                'subscriptions.cancel',
                [
                    'locale' => 'en',
                    'subscription' => $subscription->id,
                ],
            ),
        );

        $response->assertRedirect();
        $this->assertEquals('cancelled', $subscription->fresh()->status);
    }

    #[Test]
    public function user_cannot_cancel_other_users_subscription(): void
    {
        $userFactory = User::factory();
        $otherUser = $userFactory->create();
        $subscriptionFactory = Subscription::factory();
        $subscription = $subscriptionFactory->create([
            'user_id' => $otherUser->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(
            route(
                'subscriptions.cancel',
                [
                    'locale' => 'en',
                    'subscription' => $subscription->id,
                ],
            ),
        );

        $response->assertForbidden();
        $this->assertEquals('active', $subscription->fresh()->status);
    }

    #[Test]
    public function controller_uses_authorization(): void
    {
        $reflection = new \ReflectionClass(SubscriptionController::class);

        $this->assertTrue(
            $reflection->hasMethod('cancel'),
        );

        // Check if controller uses AuthorizesRequests trait
        $traits = $reflection->getTraitNames();
        $this->assertContains('Illuminate\Foundation\Auth\Access\AuthorizesRequests', $traits);
    }

    /** @return array<string, mixed> */
    private function subscribeThroughRecordingPaymentApp(): array
    {
        $paymentApp = new RecordingPaymentApplicationService;
        $this->app->instance(PaymentApplicationServiceInterface::class, $paymentApp);
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $plan = $subscriptionPlanFactory->create();

        $this->actingAs($this->user);

        $response = $this->post(
            route('subscriptions.subscribe', [
                'locale' => 'en',
                'plan' => $plan->id,
            ]),
            [
                'gateway' => 'gopay',
            ],
        );

        $response->assertRedirect(RecordingPaymentApplicationService::REDIRECT_URL);

        return $paymentApp->initiated[0];
    }
}
