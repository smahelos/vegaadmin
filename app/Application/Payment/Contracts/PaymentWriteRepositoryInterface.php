<?php

namespace App\Application\Payment\Contracts;

use App\Models\Payment;

interface PaymentWriteRepositoryInterface
{
    public function create(array $attributes): Payment;

    public function updateById(int $paymentId, array $attributes): ?Payment;

    public function applyRefund(int $paymentId, float $amount): bool;
}
