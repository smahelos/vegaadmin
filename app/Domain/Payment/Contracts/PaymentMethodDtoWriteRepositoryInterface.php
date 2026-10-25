<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\PaymentMethodDTO;
use App\Domain\Payment\DTO\PaymentMethodWriteData;

/**
 * Write repository interface for PaymentMethod DTOs.
 * Handles payment method creation, updates, and deletion.
 */
interface PaymentMethodDtoWriteRepositoryInterface
{
    /**
     * Create new payment method.
     */
    public function create(PaymentMethodWriteData $writeData): PaymentMethodDTO;
}
