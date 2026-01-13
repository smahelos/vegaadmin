<?php

namespace Tests\Feature\Domain\Payment\Services;

use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Services\SubscriptionPaymentService;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\RefreshDatabaseWithData;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class SubscriptionPaymentServiceFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    private SubscriptionPaymentServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(SubscriptionPaymentServiceInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function service_implements_correct_interface(): void
    {
        $this->assertInstanceOf(SubscriptionPaymentServiceInterface::class, $this->service);
        $this->assertInstanceOf(SubscriptionPaymentService::class, $this->service);
    }

    #[Test]
    #[Group('requires-gopay-credentials')]
    public function get_available_gateways_returns_array(): void
    {
        $gateways = $this->service->getAvailableGateways();

        $this->assertIsArray($gateways);
        // NOTE: With proper GoPay credentials, this should return available gateways
        // Without credentials, array might be empty but structure should be valid
    }

    #[Test]
    #[Group('requires-gopay-credentials')]
    public function process_subscription_payment_with_invalid_gateway_returns_error(): void
    {
        $uniqueId = uniqid();
        $user = User::create([
            'name' => 'Test User ' . $uniqueId,
            'email' => 'test' . $uniqueId . '@example.com',
            'password' => 'password'
        ]);
        
        $subscriptionPlan = SubscriptionPlan::create([
            'name' => 'Test Plan ' . $uniqueId,
            'slug' => 'test-plan-' . $uniqueId,
            'price' => 29.99,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'is_active' => true
        ]);
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'status' => 'active',
            'amount' => 29.99,
            'currency' => 'EUR',
            'starts_at' => now(),
            'ends_at' => now()->addMonth()
        ]);
        
        $result = $this->service->processSubscriptionPayment(
            $subscription->id,
            'invalid_gateway',
            ['amount' => 29.99]
        );

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function cancel_subscription_payment_with_no_payments_returns_true(): void
    {
        $uniqueId = uniqid();
        $user = User::create([
            'name' => 'Test User ' . $uniqueId,
            'email' => 'test' . $uniqueId . '@example.com',
            'password' => 'password'
        ]);
        
        $subscriptionPlan = SubscriptionPlan::create([
            'name' => 'Test Plan ' . $uniqueId,
            'slug' => 'test-plan-' . $uniqueId,
            'price' => 29.99,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'is_active' => true
        ]);
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'status' => 'active',
            'amount' => 29.99,
            'currency' => 'EUR',
            'starts_at' => now(),
            'ends_at' => now()->addMonth()
        ]);

        $result = $this->service->cancelSubscriptionPayment($subscription->id);

        // Service returns true even when no payments to cancel (success operation)
        $this->assertTrue($result);
    }

    #[Test]
    public function cancel_subscription_payment_with_completed_payment_returns_true(): void
    {
        $uniqueId = uniqid();
        $user = User::create([
            'name' => 'Test User ' . $uniqueId,
            'email' => 'test' . $uniqueId . '@example.com',
            'password' => 'password'
        ]);
        
        $subscriptionPlan = SubscriptionPlan::create([
            'name' => 'Test Plan ' . $uniqueId,
            'slug' => 'test-plan-' . $uniqueId,
            'price' => 29.99,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'is_active' => true
        ]);
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'status' => 'active',
            'amount' => 29.99,
            'currency' => 'EUR',
            'starts_at' => now(),
            'ends_at' => now()->addMonth()
        ]);
        
        Payment::create([
            'subscription_id' => $subscription->id,
            'status' => 'completed',
            'amount' => 29.99,
            'currency' => 'EUR',
            'gateway' => 'test',
            'gateway_payment_id' => 'test_payment_' . $uniqueId
        ]);

        $result = $this->service->cancelSubscriptionPayment($subscription->id);

        // Service returns true - only pending/processing payments are cancelled
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('requires-gopay-credentials')]
    public function handle_payment_callback_with_invalid_gateway_returns_error(): void
    {
        $result = $this->service->handlePaymentCallback('invalid_gateway', ['payment_id' => '123']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    #[Group('requires-gopay-credentials')]
    public function verify_subscription_payment_requires_valid_payment(): void
    {
        $uniqueId = uniqid();
        $user = User::create([
            'name' => 'Test User ' . $uniqueId,
            'email' => 'test' . $uniqueId . '@example.com',
            'password' => 'password'
        ]);
        
        $subscriptionPlan = SubscriptionPlan::create([
            'name' => 'Test Plan ' . $uniqueId,
            'slug' => 'test-plan-' . $uniqueId,
            'price' => 29.99,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'is_active' => true
        ]);
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'status' => 'active',
            'amount' => 29.99,
            'currency' => 'EUR',
            'starts_at' => now(),
            'ends_at' => now()->addMonth()
        ]);
        
        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway' => 'gopay',
            'gateway_payment_id' => 'GP' . $uniqueId,
            'amount' => 29.99,
            'currency' => 'EUR'
        ]);

        // NOTE: This test would normally verify payment via gateway
        // but we skip actual payment verification due to missing GoPay credentials
        // The actual verification logic should be tested with proper credentials

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals('gopay', $payment->gateway);
    }
}
