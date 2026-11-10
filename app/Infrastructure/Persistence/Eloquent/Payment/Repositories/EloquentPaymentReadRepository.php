<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Application\Payment\Contracts\PaymentReadRepositoryInterface;
use App\Models\Payment;

class EloquentPaymentReadRepository implements PaymentReadRepositoryInterface
{
    public function findByGatewayPaymentIdForUpdate(string $gateway, ?string $gatewayPaymentId): ?Payment
    {
        if (!$gatewayPaymentId) {
            return null;
        }

        // Both columns, so the lock uses the (gateway, gateway_payment_id) index instead of locking the whole table
        $query = Payment::where('gateway', $gateway);
        $query->where('gateway_payment_id', $gatewayPaymentId);
        $query->lockForUpdate();

        return $query->first();
    }

    public function findPendingBySubscription(int $subscriptionId): array
    {
        $query = Payment::where('subscription_id', $subscriptionId);
        $query->whereIn('status', ['pending', 'processing']);
        $payments = $query->get();

        return $payments->all();
    }
}
