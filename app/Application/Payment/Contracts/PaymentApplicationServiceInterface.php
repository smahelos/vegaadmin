<?php

namespace App\Application\Payment\Contracts;

use App\Models\Subscription;
use App\Models\Payment;

/**
 * Application layer facade orchestrating payment flows across domain services.
 * Coordinates QrPaymentService, SubscriptionPaymentService and gateway registry.
 */
interface PaymentApplicationServiceInterface
{
    /**
     * Initiate a subscription payment and return redirect / status payload.
     *
     * @param Subscription $subscription
     * @param string $gateway Gateway key (e.g. gopay, stub)
     * @param array $data Additional gateway metadata (customer / return URLs)
     * @return array{success:bool, payment_id?:int, redirect_url?:string|null, status?:string, error?:string}
     */
    public function initiateSubscription(Subscription $subscription, string $gateway, array $data = []): \App\Application\Payment\DTO\SubscriptionPaymentResultDTO;

    /**
     * Handle asynchronous gateway callback.
     *
     * @param string $gateway
     * @param array $payload Raw callback data
     * @return array{success:bool, payment_id?:int, status?:string, error?:string}
     */
    public function handleCallback(string $gateway, array $payload): \App\Application\Payment\DTO\SubscriptionPaymentResultDTO;

    /**
     * Verify payment status via gateway.
     *
     * @param Payment $payment
     * @return array{success:bool, status?:string, amount?:float, currency?:string, error?:string}
     */
    public function verify(Payment $payment): \App\Application\Payment\DTO\SubscriptionPaymentResultDTO;

    /**
     * Refund a payment (full or partial). Amount null = remaining.
     *
     * @param Payment $payment
     * @param float|null $amount
     * @return array{success:bool, payment_id?:int, refunded_amount?:float, currency?:string, error?:string}
     */
    public function refund(Payment $payment, ?float $amount = null): \App\Application\Payment\DTO\RefundResultDTO;

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
