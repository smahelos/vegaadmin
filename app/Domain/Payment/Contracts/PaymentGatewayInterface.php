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
     * Cancel payment
     */
    public function cancelPayment(string $paymentId): bool;

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get gateway name
     */
    public function getName(): string;

    /**
     * Get human readable display name
     */
    public function getDisplayName(): string;
}
