<?php

namespace App\Application\Payment\Contracts;

use App\Models\Payment;

/**
 * Read-only operations for Payment aggregate.
 */
interface PaymentReadRepositoryInterface
{
    public function findById(int $id): ?Payment;

    public function findByGatewayPaymentId(?string $gatewayPaymentId): ?Payment;
    
    /** @return array<int,Payment> */
    public function findPendingBySubscription(int $subscriptionId): array;
}
