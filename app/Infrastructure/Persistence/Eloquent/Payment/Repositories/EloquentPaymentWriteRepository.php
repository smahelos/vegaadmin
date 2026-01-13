<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Application\Payment\Contracts\PaymentWriteRepositoryInterface;
use App\Models\Payment;

class EloquentPaymentWriteRepository implements PaymentWriteRepositoryInterface
{
    public function create(array $attributes): Payment
    {
        return Payment::create($attributes);
    }

    public function updateById(int $id, array $data): ?Payment
    {
        $model = Payment::query()->find($id);
        if (!$model) { return null; }
        if (!empty($data)) {
            $model->fill($data);
            $model->save();
        }
        // Load relations to keep consistency with read DTOs (e.g., category)
        return $model->load(['children']);
    }

    /**
     * Register a refunded amount (partial or full) updating status accordingly.
     *
     * @param float $amount Amount refunded in this operation (>=0)
     * @return bool
     */
    public function applyRefund(int $paymentId, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }
        $payment = Payment::query()->find($paymentId);
        if (!$payment) {
            return false;
        }
        $current = (float) ($payment->refunded_amount ?? 0.0);
        $newTotal = round($current + $amount, 2);
        $payment->refunded_amount = (string) $newTotal; // decimal cast expects stringable numeric
        if ($newTotal + 0.0001 < (float) $payment->amount) {
            $payment->status = 'partially_refunded';
        } else {
            $payment->status = 'refunded';
            $payment->refunded_amount = (string) (float)$payment->amount; // clamp
        }
        return $payment->save();
    }
}
