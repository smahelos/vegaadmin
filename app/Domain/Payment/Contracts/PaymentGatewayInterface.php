<?php

namespace App\Domain\Payment\Contracts;

/**
 * Interface for payment gateway implementations
 */
interface PaymentGatewayInterface
{
    /**
     * Create a payment for subscription (array payload only)
     */
    public function createPayment(array $paymentData): array;

    /**
     * Process payment return/callback
     */
    public function processPaymentReturn(array $data): array;

    /**
     * Verify payment status
     */
    public function verifyPayment(string $paymentId): array;

    /**
     * Cancel payment
     */
    public function cancelPayment(string $paymentId): bool;

    /**
     * Refund payment
     */
    public function refundPayment(string $paymentId, float $amount = null): array;

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId): string;

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array;

    /**
     * Check if gateway is available
     */
    public function isAvailable(): bool;

    /**
     * Get gateway name
     */
    public function getName(): string;

    /**
     * Get human readable display name
     */
    public function getDisplayName(): string;
}
