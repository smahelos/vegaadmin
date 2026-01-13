<?php

namespace Tests\Feature\Models;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function payment_can_be_created_with_valid_data(): void
    {
        $subscription = Subscription::factory()->create();

        $paymentData = [
            'subscription_id' => $subscription->id,
            'gateway' => 'gopay',
            'gateway_payment_id' => 'GP123456789',
            'status' => 'completed',
            'amount' => 29.99,
            'currency' => 'EUR',
            'payment_method' => 'card',
            'gateway_data' => ['transaction_id' => 'TXN123'],
        ];

        $payment = Payment::create($paymentData);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($subscription->id, $payment->subscription_id);
        $this->assertEquals('gopay', $payment->gateway);
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals(29.99, $payment->amount);
    }

    #[Test]
    public function payment_has_subscription_relationship(): void
    {
        $payment = Payment::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment->subscription());
        $this->assertInstanceOf(Subscription::class, $payment->subscription);
    }

    #[Test]
    public function payment_completed_scope_returns_only_completed_payments(): void
    {
        $completedPayment = Payment::factory()->create(['status' => 'completed']);
        $pendingPayment = Payment::factory()->create(['status' => 'pending']);

        $completedPayments = Payment::completed()->get();

        $this->assertCount(1, $completedPayments);
        $this->assertEquals($completedPayment->id, $completedPayments->first()->id);
    }

    #[Test]
    public function payment_failed_scope_returns_only_failed_payments(): void
    {
        $completedPayment = Payment::factory()->create(['status' => 'completed']);
        $failedPayment = Payment::factory()->create(['status' => 'failed']);

        $failedPayments = Payment::failed()->get();

        $this->assertCount(1, $failedPayments);
        $this->assertEquals($failedPayment->id, $failedPayments->first()->id);
    }

    #[Test]
    public function payment_pending_scope_returns_only_pending_payments(): void
    {
        $completedPayment = Payment::factory()->create(['status' => 'completed']);
        $pendingPayment = Payment::factory()->create(['status' => 'pending']);

        $pendingPayments = Payment::pending()->get();

        $this->assertCount(1, $pendingPayments);
        $this->assertEquals($pendingPayment->id, $pendingPayments->first()->id);
    }

    #[Test]
    public function payment_is_completed_returns_correct_boolean(): void
    {
        $completedPayment = Payment::factory()->create(['status' => 'completed']);
        $pendingPayment = Payment::factory()->create(['status' => 'pending']);

        $this->assertTrue($completedPayment->isCompleted());
        $this->assertFalse($pendingPayment->isCompleted());
    }

    #[Test]
    public function payment_is_failed_returns_correct_boolean(): void
    {
        $failedPayment = Payment::factory()->create(['status' => 'failed']);
        $completedPayment = Payment::factory()->create(['status' => 'completed']);

        $this->assertTrue($failedPayment->isFailed());
        $this->assertFalse($completedPayment->isFailed());
    }

    #[Test]
    public function payment_is_pending_returns_correct_boolean(): void
    {
        $pendingPayment = Payment::factory()->create(['status' => 'pending']);
        $completedPayment = Payment::factory()->create(['status' => 'completed']);

        $this->assertTrue($pendingPayment->isPending());
        $this->assertFalse($completedPayment->isPending());
    }

    #[Test]
    public function payment_mark_as_completed_updates_status(): void
    {
        $payment = Payment::factory()->create(['status' => 'pending']);

        $result = $payment->markAsCompleted();

        $this->assertTrue($result);
        $this->assertEquals('completed', $payment->fresh()->status);
    }

    #[Test]
    public function payment_mark_as_failed_updates_status_and_reason(): void
    {
        $payment = Payment::factory()->create(['status' => 'pending']);

        $result = $payment->markAsFailed('Card declined');

        $this->assertTrue($result);
        $this->assertEquals('failed', $payment->fresh()->status);
        $this->assertEquals('Card declined', $payment->fresh()->failure_reason);
    }

    #[Test]
    public function payment_casts_attributes_correctly(): void
    {
        $payment = Payment::factory()->create([
            'amount' => '29.99',
            'gateway_data' => ['key' => 'value'],
        ]);

        // Laravel's decimal cast returns a string, not float
        $this->assertIsString($payment->amount);
        $this->assertEquals('29.99', $payment->amount);
        $this->assertIsArray($payment->gateway_data);
    }
}
