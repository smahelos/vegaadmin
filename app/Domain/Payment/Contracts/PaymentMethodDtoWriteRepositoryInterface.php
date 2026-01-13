<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\PaymentMethodDTO;
use App\Domain\Payment\DTO\PaymentMethodWriteData;
use App\Domain\Payment\ValueObjects\PaymentMethodId;

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

    /**
     * Update existing payment method.
     */
    public function update(PaymentMethodId $id, PaymentMethodWriteData $writeData): PaymentMethodDTO;

    /**
     * Delete payment method by ID.
     */
    public function delete(PaymentMethodId $id): void;
}
