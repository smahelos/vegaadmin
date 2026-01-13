<?php

namespace Tests\Feature\Application\Payment;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentApplicationServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private PaymentApplicationServiceInterface $appService;

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal stubs bound into container for predictable behavior.
        $this->app->bind(SubscriptionPaymentServiceInterface::class, function () {
            return new class implements SubscriptionPaymentServiceInterface {
                public array $state = [];
                public function processSubscriptionPayment(int $subscriptionId, string $gateway, array $paymentData): array {
                    $payment = Payment::create([
                        'subscription_id' => $subscriptionId,
                        'amount' => Subscription::find($subscriptionId)->subscriptionPlan->price,
                        'currency' => Subscription::find($subscriptionId)->subscriptionPlan->currency,
                        'gateway' => $gateway,
                        'status' => 'pending',
                        'gateway_payment_id' => 'GW123',
                        'transaction_data' => json_encode($paymentData),
                    ]);
                    $this->state['payment_id'] = $payment->id;
                    return [
                        'success' => true,
                        'payment_id' => $payment->id,
                        'redirect_url' => 'https://example.test/pay/'.$payment->id,
                        'status' => 'pending',
                    ];
                }
                public function handlePaymentCallback(string $gateway, array $callbackData): array {
                    $payment = Payment::find($callbackData['order_id']);
                    if ($payment) {
                        $payment->update(['status' => 'completed']);
                        return ['success' => true, 'payment_id' => $payment->id, 'status' => 'completed'];
                    }
                    return ['success' => false, 'error' => 'Payment not found'];
                }
                public function verifySubscriptionPayment(int $paymentId): array {
                    $payment = Payment::find($paymentId);
                    return ['success' => true, 'status' => $payment->status];
                }
                public function refundSubscriptionPayment(int $paymentId, ?float $amount = null): array {
                    $payment = Payment::find($paymentId);
                    $amount = $amount ?? $payment->amount;
                    $payment->update(['status' => $amount < $payment->amount ? 'partially_refunded' : 'refunded']);
                    return ['success' => true, 'payment_id' => $payment->id, 'refunded_amount' => $amount, 'currency' => $payment->currency];
                }
                public function cancelSubscriptionPayment(int $subscriptionId): bool { return true; }
                public function getSupportedGateways(): array { return ['stub']; }
                public function getGateway(string $gateway): ?\App\Domain\Payment\Contracts\PaymentGatewayInterface { return null; }
                public function getAvailableGateways(): array { return [['name' => 'stub']]; }
                public function supportsRecurringPayments(string $gateway): bool { return true; }
                public function getPaymentStatus(int $paymentId): array { $p = Payment::find($paymentId); return ['success' => (bool)$p, 'payment_id' => $paymentId, 'status' => $p?->status]; }
                public function inspectPayment(int $paymentId): array { return ['success' => true]; }
            };
        });
        $this->app->bind(QrPaymentServiceInterface::class, function () {
            return new class implements QrPaymentServiceInterface {
                public function generateQrCodeBase64($invoice): ?string { return null; }
                public function hasRequiredPaymentInfo(\App\Domain\Payment\DTO\QrPaymentPayload $payload): bool { return false; }
                public function generateQrStringForCountry($invoice, string $countryCode): ?string { return null; }
                public function getSupportedCountries(): array { return []; }
            };
        });

        $this->appService = $this->app->make(PaymentApplicationServiceInterface::class);
    }

    private function createSubscription(): Subscription
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price' => 100.00,
            'currency' => 'CZK',
        ]);
        return Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => $plan->price,
            'currency' => $plan->currency,
        ]);
    }

    #[Test]
    public function initiate_subscription_flow_creates_payment_and_redirect_url(): void
    {
        $subscription = $this->createSubscription();
        $res = $this->appService->initiateSubscription($subscription, 'stub');
        $this->assertTrue($res->success);
        $this->assertNotEmpty($res->paymentId);
        $this->assertStringContainsString('/pay/', $res->redirectUrl);
        $this->assertDatabaseHas('payments', [
            'id' => $res->paymentId,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function callback_updates_payment_status(): void
    {
        $subscription = $this->createSubscription();
        $init = $this->appService->initiateSubscription($subscription, 'stub');
        $paymentId = $init->paymentId;
        $callback = $this->appService->handleCallback('stub', ['order_id' => $paymentId]);
        $this->assertTrue($callback->success);
        $this->assertEquals('completed', $callback->status);
        $this->assertDatabaseHas('payments', [ 'id' => $paymentId, 'status' => 'completed']);
    }

    #[Test]
    public function verify_returns_current_status(): void
    {
        $subscription = $this->createSubscription();
        $init = $this->appService->initiateSubscription($subscription, 'stub');
        $payment = Payment::find($init->paymentId);
        $verify = $this->appService->verify($payment);
        $this->assertTrue($verify->success);
        $this->assertEquals('pending', $verify->status);
    }

    #[Test]
    public function refund_full_sets_refunded_status(): void
    {
        $subscription = $this->createSubscription();
        $init = $this->appService->initiateSubscription($subscription, 'stub');
        $payment = Payment::find($init->paymentId);
        $payment->update(['status' => 'completed']);
        $refund = $this->appService->refund($payment, null);
        $this->assertTrue($refund->success);
        $this->assertEquals($payment->amount, $refund->refundedAmount);
        $this->assertDatabaseHas('payments', [ 'id' => $payment->id, 'status' => 'refunded']);
    }

    #[Test]
    public function partial_refund_sets_partially_refunded_status(): void
    {
        $subscription = $this->createSubscription();
        $init = $this->appService->initiateSubscription($subscription, 'stub');
        $payment = Payment::find($init->paymentId);
        $payment->update(['status' => 'completed']);
        $refund = $this->appService->refund($payment, 10.0);
        $this->assertTrue($refund->success);
        $this->assertEquals(10.0, $refund->refundedAmount);
        $this->assertDatabaseHas('payments', [ 'id' => $payment->id, 'status' => 'partially_refunded']);
    }
}
