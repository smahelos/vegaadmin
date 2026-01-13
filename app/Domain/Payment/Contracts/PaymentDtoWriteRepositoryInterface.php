<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\PaymentDTO;
use App\Domain\Payment\DTO\PaymentWriteData;

interface PaymentDtoWriteRepositoryInterface
{
    public function create(PaymentWriteData $attributes): PaymentDTO;

    public function updateById(int $paymentId, PaymentWriteData $attributes): PaymentDTO;

    public function applyRefund(int $paymentId, float $amount): bool;
}
