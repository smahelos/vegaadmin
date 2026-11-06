<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Application\Payment\Contracts\PaymentReadRepositoryInterface;
use App\Models\Payment;

class EloquentPaymentReadRepository implements PaymentReadRepositoryInterface
{
    public function findById(int $id): ?Payment
    {
        return Payment::find($id);
    }

    public function findByGatewayPaymentId(?string $gatewayPaymentId): ?Payment
    {
        if (!$gatewayPaymentId) {
            return null;
        }

        $query = Payment::where('gateway_payment_id', $gatewayPaymentId);

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
