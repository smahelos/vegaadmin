<?php

namespace App\Application\Payment\Contracts;

use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Models\Payment;
use App\Models\Subscription;

/**
 * Application layer facade orchestrating payment flows across domain services.
 * Coordinates QrPaymentService, SubscriptionPaymentService and gateway registry.
 */
interface PaymentApplicationServiceInterface
{
    /**
     * Initiate a subscription payment and return redirect / status payload.
     *
     * @param  string  $gateway  Gateway key (e.g. gopay, stub)
     * @param  array  $data  Additional gateway metadata (customer / return URLs)
     */
    public function initiateSubscription(
        Subscription $subscription,
        string $gateway,
        array $data = [],
    ): SubscriptionPaymentResultDTO;

    /**
     * Handle asynchronous gateway callback.
     *
     * @param  array  $payload  Raw callback data
     */
    public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO;

    /**
     * List available payment gateways (delegated from domain service layer).
     *
     * @return array<int, array|string>|string[]
     */
    public function getAvailableGateways(): array;

    /**
     * Cancel all pending/processing payments for a subscription (best-effort) and update its state.
     */
    public function cancelSubscription(Subscription $subscription): bool;
}
