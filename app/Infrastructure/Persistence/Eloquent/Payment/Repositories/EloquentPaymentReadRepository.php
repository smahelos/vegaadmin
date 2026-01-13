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
        if (!$gatewayPaymentId) return null;
        return Payment::where('gateway_payment_id', $gatewayPaymentId)->first();
    }

    public function findPendingBySubscription(int $subscriptionId): array
    {
        return Payment::where('subscription_id', $subscriptionId)
            ->whereIn('status', ['pending', 'processing'])
            ->get()
            ->all();
    }
}
