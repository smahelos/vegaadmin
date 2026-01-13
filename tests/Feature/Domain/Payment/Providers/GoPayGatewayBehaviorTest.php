<?php

namespace Tests\Feature\Domain\Payment\Providers;

use App\Infrastructure\Providers\Payment\GoPayGateway as RealGoPayGateway;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\Payment\StubPayments;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

/**
 * Behavior tests for GoPayGateway using StubPayments (no real HTTP calls).
 */
class GoPayGatewayBehaviorTest extends TestCase
{
    use RefreshDatabaseWithData;

    private RealGoPayGateway $gateway;
    private StubPayments $stub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stub = new StubPayments();
        $this->gateway = new RealGoPayGateway($this->stub);
    }

    private function makeSubscription(): Subscription
    {
        $unique = uniqid();
        $user = User::factory()->create(['email' => 'gopay_' . $unique . '@example.com']);
        $plan = SubscriptionPlan::create([
            'name' => 'GP Plan ' . $unique,
            'price' => 25.00,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'is_active' => true,
            'trial_days' => 0,
        ]);
        return Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => 25.00,
            'currency' => 'EUR',
        ]);
    }

    #[Test]
    public function create_payment_success_returns_redirect(): void
    {
        $sub = $this->makeSubscription();
        $payload = [
            'subscription_id' => $sub->id,
            'amount' => $sub->amount,
            'currency' => $sub->currency,
        ];
        $result = $this->gateway->createPayment($payload);
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['payment_id']);
        $this->assertNotEmpty($result['payment_url']);
        $this->assertEquals('GP-', substr($result['payment_id'], 0, 3));
    }

    #[Test]
    public function create_payment_failure_returns_error(): void
    {
        $sub = $this->makeSubscription();
        $this->stub->failCreate = true;
        $payload = [
            'subscription_id' => $sub->id,
            'amount' => $sub->amount,
            'currency' => $sub->currency,
        ];
        $result = $this->gateway->createPayment($payload);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function verify_maps_status(): void
    {
        $sub = $this->makeSubscription();
        $payload = [
            'subscription_id' => $sub->id,
            'amount' => $sub->amount,
            'currency' => $sub->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $status = $this->gateway->verifyPayment($create['payment_id']);
        $this->assertTrue($status['success']);
        $this->assertEquals('pending', $status['status']);
    }

    #[Test]
    public function cancel_sets_cancelled_state(): void
    {
        $sub = $this->makeSubscription();
        $payload = [
            'subscription_id' => $sub->id,
            'amount' => $sub->amount,
            'currency' => $sub->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $ok = $this->gateway->cancelPayment($create['payment_id']);
        $this->assertTrue($ok);
        $status = $this->gateway->verifyPayment($create['payment_id']);
        $this->assertEquals('cancelled', $status['status']);
    }

    #[Test]
    public function refund_full_sets_refunded(): void
    {
        $sub = $this->makeSubscription();
        $payload = [
            'subscription_id' => $sub->id,
            'amount' => $sub->amount,
            'currency' => $sub->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $refund = $this->gateway->refundPayment($create['payment_id']);
        $this->assertTrue($refund['success']);
        $status = $this->gateway->verifyPayment($create['payment_id']);
        $this->assertEquals('refunded', $status['status']);
    }

    #[Test]
    public function refund_partial_sets_partially_refunded(): void
    {
        $sub = $this->makeSubscription();
        $payload = [
            'subscription_id' => $sub->id,
            'amount' => $sub->amount,
            'currency' => $sub->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $refund = $this->gateway->refundPayment($create['payment_id'], 5.00);
        $this->assertTrue($refund['success']);
        $status = $this->gateway->verifyPayment($create['payment_id']);
        $this->assertEquals('partially_refunded', $status['status']);
    }

    #[Test]
    public function multiple_partial_refunds_accumulate_then_full_refund(): void
    {
        $sub = $this->makeSubscription();
        $payload = [
            'subscription_id' => $sub->id,
            'amount' => $sub->amount,
            'currency' => $sub->currency,
        ];
        $create = $this->gateway->createPayment($payload);
        $pid = $create['payment_id'];

        // First partial refund
        $r1 = $this->gateway->refundPayment($pid, 3.00);
        $this->assertTrue($r1['success']);
        $s1 = $this->gateway->verifyPayment($pid);
        $this->assertEquals('partially_refunded', $s1['status']);

        // Second partial refund (still partial)
        $r2 = $this->gateway->refundPayment($pid, 2.00);
        $this->assertTrue($r2['success']);
        $s2 = $this->gateway->verifyPayment($pid);
        $this->assertEquals('partially_refunded', $s2['status']);

        // Final full refund (no amount => remaining)
        $r3 = $this->gateway->refundPayment($pid);
        $this->assertTrue($r3['success']);
        $s3 = $this->gateway->verifyPayment($pid);
        $this->assertEquals('refunded', $s3['status']);
    }
}
