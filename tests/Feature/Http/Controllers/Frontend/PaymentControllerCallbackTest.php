<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Payment\DTO\RefundResultDTO;
use App\Models\Subscription;
use App\Models\Payment;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentControllerCallbackTest extends TestCase
{
    #[Test]
    public function return_endpoint_redirects_on_success_completed(): void
    {
        $this->swap(PaymentApplicationServiceInterface::class, new class implements PaymentApplicationServiceInterface {
            public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true,'status'=>'completed']); }
            public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return new RefundResultDTO(success: true, paymentId: $payment->id ?? 0, refundedAmount: $amount, currency: 'CZK', status: 'refunded'); }
            public function getAvailableGateways(): array { return []; }
            public function cancelSubscription(Subscription $subscription): bool { return true; }
        });

        $response = $this->get('/payment/return?gateway=gopay');
        $response->assertRedirect();
        $this->assertStringContainsString('/my-subscription', $response->headers->get('Location'));
    }

    #[Test]
    public function return_endpoint_redirects_on_failed(): void
    {
        $this->swap(PaymentApplicationServiceInterface::class, new class implements PaymentApplicationServiceInterface {
            public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true,'status'=>'failed']); }
            public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return new RefundResultDTO(success: true, paymentId: $payment->id ?? 0, refundedAmount: $amount, currency: 'CZK', status: 'refunded'); }
            public function getAvailableGateways(): array { return []; }
            public function cancelSubscription(Subscription $subscription): bool { return true; }
        });

        $response = $this->get('/payment/return?gateway=gopay');
        $response->assertRedirectContains('subscriptions');
    }

    #[Test]
    public function return_endpoint_redirects_on_pending(): void
    {
        $this->swap(PaymentApplicationServiceInterface::class, new class implements PaymentApplicationServiceInterface {
            public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true,'status'=>'pending']); }
            public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return new RefundResultDTO(success: true, paymentId: $payment->id ?? 0, refundedAmount: $amount, currency: 'CZK', status: 'refunded'); }
            public function getAvailableGateways(): array { return []; }
            public function cancelSubscription(Subscription $subscription): bool { return true; }
        });

        $response = $this->get('/payment/return?gateway=gopay');
        $response->assertRedirectContains('subscriptions');
    }

    #[Test]
    public function notify_endpoint_returns_ok_on_success(): void
    {
        $this->swap(PaymentApplicationServiceInterface::class, new class implements PaymentApplicationServiceInterface {
            public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true,'status'=>'completed']); }
            public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return new RefundResultDTO(success: true, paymentId: $payment->id ?? 0, refundedAmount: $amount, currency: 'CZK', status: 'refunded'); }
            public function getAvailableGateways(): array { return []; }
            public function cancelSubscription(Subscription $subscription): bool { return true; }
        });

        $response = $this->postJson('/payment/notify', ['gateway'=>'gopay']);
        $response->assertOk()->assertJson(['status'=>'ok']);
    }

    #[Test]
    public function notify_endpoint_returns_error_on_failure(): void
    {
        $this->swap(PaymentApplicationServiceInterface::class, new class implements PaymentApplicationServiceInterface {
            public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>false,'error'=>'x']); }
            public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success'=>true]); }
            public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return new RefundResultDTO(success: true, paymentId: $payment->id ?? 0, refundedAmount: $amount, currency: 'CZK', status: 'refunded'); }
            public function getAvailableGateways(): array { return []; }
            public function cancelSubscription(Subscription $subscription): bool { return true; }
        });

        $response = $this->postJson('/payment/notify', ['gateway'=>'gopay']);
        $response->assertOk()->assertJson(['status'=>'error']);
    }
}
