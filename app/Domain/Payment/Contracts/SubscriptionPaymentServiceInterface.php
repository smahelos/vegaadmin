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
    public function processSubscriptionPayment(int $subscriptionId, string $gateway, array $paymentData): array;

    /**
     * Handle payment callback
     */
    public function handlePaymentCallback(string $gateway, array $callbackData): array;

    /**
     * Cancel subscription payment
     */
    public function cancelSubscriptionPayment(int $subscriptionId): bool;

    /**
     * Verify subscription payment
     */
    public function verifySubscriptionPayment(int $paymentId): array;

    /**
     * Get available payment gateways
     */
    public function getAvailableGateways(): array;

    /**
     * Check if gateway supports recurring payments
     */
    public function supportsRecurringPayments(string $gateway): bool;

    /**
     * Refund a completed payment (full or partial) and return result meta.
     * Expected keys on success: success=true, payment_id, refunded_amount, currency.
     * On failure: success=false, error.
     */
    public function refundSubscriptionPayment(int $paymentId, ?float $amount = null): array;

    /**
     * Return an associative array with diagnostic info (for reporting / admin UI) about a payment.
     * Should be read-only and never mutate state.
     */
    public function inspectPayment(int $paymentId): array;
}
