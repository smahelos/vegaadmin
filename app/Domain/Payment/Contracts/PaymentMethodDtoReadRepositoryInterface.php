<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\PaymentMethodDTO;
use App\Domain\Payment\ValueObjects\PaymentMethodId;

/**
 * Read-only repository interface for PaymentMethod DTOs.
 * Provides clean Domain layer access to payment method data.
 */
interface PaymentMethodDtoReadRepositoryInterface
{
    /**
     * Get all payment methods as DTOs.
     */
    public function all(): array;

    /**
     * Get payment methods formatted for dropdown (id => slug).
     */
    public function getAllForDropdown(): array;

    /**
     * Find payment method by ID.
     */
    public function findById(PaymentMethodId $id): ?PaymentMethodDTO;

    /**
     * Find payment method by slug.
     */
    public function findBySlug(string $slug): ?PaymentMethodDTO;
}
