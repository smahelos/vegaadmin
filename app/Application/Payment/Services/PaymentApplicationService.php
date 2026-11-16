<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;

/**
 * The payment gateways a plan is paid through: the same for anyone, so outside the gate.
 */
final class PaymentApplicationService
{
    public function __construct(
        private readonly SubscriptionPaymentServiceInterface $subscriptionPayments,
    ) {}

    /**
     * @return array<string, array{name: string, supported_currencies: list<string>, supports_recurring: bool}>
     */
    public function getAvailableGateways(): array
    {
        return $this->subscriptionPayments->getAvailableGateways();
    }
}
