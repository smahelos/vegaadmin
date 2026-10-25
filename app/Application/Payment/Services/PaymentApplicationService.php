<?php

namespace App\Application\Payment\Services;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO; // placeholder for future QR flows
use App\Application\Payment\Traits\ApplicationUseCaseLogging;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Models\Payment;
use App\Models\Subscription;

/**
 * Concrete application service orchestrating payment use-cases.
 * Thin layer delegating to domain services; contains no persistence logic itself.
 */
class PaymentApplicationService implements PaymentApplicationServiceInterface
{
    use ApplicationUseCaseLogging;

    public function __construct(
        private readonly SubscriptionPaymentServiceInterface $subscriptionPayments,
    ) {}

    /** {@inheritDoc} */
    public function initiateSubscription(
        Subscription $subscription,
        string $gateway,
        array $data = [],
    ): SubscriptionPaymentResultDTO {
        return $this->withUseCaseLog(
            'initiate_subscription',
            [
                'subscription_id' => $subscription->id,
                'gateway' => $gateway,
            ],
            function () use ($subscription, $gateway, $data) {
                $result = $this->subscriptionPayments->processSubscriptionPayment(
                    $subscription->id,
                    $gateway,
                    $data,
                );

                return SubscriptionPaymentResultDTO::fromArray($result);
            },
        );
    }

    /** {@inheritDoc} */
    public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
    {
        return $this->withUseCaseLog(
            'handle_callback',
            [
                'gateway' => $gateway,
            ],
            function () use ($gateway, $payload) {
                return SubscriptionPaymentResultDTO::fromArray(
                    $this->subscriptionPayments->handlePaymentCallback($gateway, $payload),
                );
            },
        );
    }

    /** {@inheritDoc} */
    public function getAvailableGateways(): array
    {
        return $this->subscriptionPayments->getAvailableGateways();
    }

    /** {@inheritDoc} */
    public function cancelSubscription(Subscription $subscription): bool
    {
        return $this->subscriptionPayments->cancelSubscriptionPayment($subscription->id);
    }
}
