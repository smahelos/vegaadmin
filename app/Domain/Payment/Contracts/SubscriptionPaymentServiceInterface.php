<?php

namespace App\Domain\Payment\Contracts;

/**
 * Interface for subscription payment service
 */
interface SubscriptionPaymentServiceInterface
{
    /**
     * Process subscription payment
     */
    public function processSubscriptionPayment(
        int $subscriptionId,
        string $gateway,
        array $paymentData,
    ): array;

    /**
     * Handle payment callback
     */
    public function handlePaymentCallback(string $gateway, array $callbackData): array;

    /**
     * Cancel subscription payment
     */
    public function cancelSubscriptionPayment(int $subscriptionId): bool;

    /**
     * Get available payment gateways
     */
    public function getAvailableGateways(): array;
}
