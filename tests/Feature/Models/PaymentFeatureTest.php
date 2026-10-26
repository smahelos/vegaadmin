<?php

namespace Tests\Feature\Models;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
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

        $this->assertInstanceOf(BelongsTo::class, $payment->subscription());
        $this->assertInstanceOf(Subscription::class, $payment->subscription);
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
