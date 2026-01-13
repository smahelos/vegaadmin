<?php

namespace Tests\Traits;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Payment\DTO\RefundResultDTO;
use App\Models\Subscription;
use App\Models\Payment;

/**
 * Provides helper to bind a lightweight synthetic PaymentApplicationService for controller view tests.
 */
trait WithSyntheticPaymentAppService
{
    protected function bindSyntheticPaymentAppService(?array $gateways = null): void
    {
        $gateways = $gateways ?? [
            'gopay' => [
                'name' => 'GoPay', 
                'supported_currencies' => ['CZK', 'EUR'], 
                'supports_recurring' => false
            ]
        ];
        $this->app->bind(PaymentApplicationServiceInterface::class, function () use ($gateways) {
            return new class($gateways) implements PaymentApplicationServiceInterface {
                public function __construct(private array $gateways) {}
                public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => false]); }
                public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => false]); }
                public function verify(Payment $payment): SubscriptionPaymentResultDTO { return SubscriptionPaymentResultDTO::fromArray(['success' => false]); }
                public function refund(Payment $payment, ?float $amount = null): RefundResultDTO { return RefundResultDTO::fromArray(['success' => false]); }
                public function getAvailableGateways(): array { return $this->gateways; }
                public function cancelSubscription(Subscription $subscription): bool { return false; }
            };
        });
    }
}
