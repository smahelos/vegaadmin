<?php

namespace App\Application\Payment\Services;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface; // placeholder for future QR flows
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Application\Payment\DTO\RefundResultDTO;
use App\Application\Payment\Traits\ApplicationUseCaseLogging;
use App\Models\Subscription;
use App\Models\Payment;

/**
 * Concrete application service orchestrating payment use-cases.
 * Thin layer delegating to domain services; contains no persistence logic itself.
 */
class PaymentApplicationService implements PaymentApplicationServiceInterface
{
    use ApplicationUseCaseLogging;
    public function __construct(
        private readonly SubscriptionPaymentServiceInterface $subscriptionPayments,
        private readonly QrPaymentServiceInterface $qrPayments,
    ) {}

    /** @inheritDoc */
    public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): SubscriptionPaymentResultDTO
    {
        return $this->withUseCaseLog('initiate_subscription', [
            'subscription_id' => $subscription->id,
            'gateway' => $gateway,
        ], function () use ($subscription, $gateway, $data) {
            $result = $this->subscriptionPayments->processSubscriptionPayment($subscription->id, $gateway, $data);
            return SubscriptionPaymentResultDTO::fromArray($result);
        });
    }

    /** @inheritDoc */
    public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
    {
        return $this->withUseCaseLog('handle_callback', [
            'gateway' => $gateway,
        ], function () use ($gateway, $payload) {
            return SubscriptionPaymentResultDTO::fromArray(
                $this->subscriptionPayments->handlePaymentCallback($gateway, $payload)
            );
        });
    }

    /** @inheritDoc */
    public function verify(Payment $payment): SubscriptionPaymentResultDTO
    {
        return $this->withUseCaseLog('verify_payment', [
            'payment_id' => $payment->id,
        ], function () use ($payment) {
            return SubscriptionPaymentResultDTO::fromArray(
                $this->subscriptionPayments->verifySubscriptionPayment($payment->id)
            );
        });
    }

    /** @inheritDoc */
    public function refund(Payment $payment, ?float $amount = null): RefundResultDTO
    {
        return $this->withUseCaseLog('refund_payment', [
            'payment_id' => $payment->id,
            'amount' => $amount,
        ], function () use ($payment, $amount) {
            return RefundResultDTO::fromArray(
                $this->subscriptionPayments->refundSubscriptionPayment($payment->id, $amount)
            );
        });
    }

    /** @inheritDoc */
    public function getAvailableGateways(): array
    {
        return $this->subscriptionPayments->getAvailableGateways();
    }

    /** @inheritDoc */
    public function cancelSubscription(Subscription $subscription): bool
    {
        return $this->subscriptionPayments->cancelSubscriptionPayment($subscription->id);
    }
}
