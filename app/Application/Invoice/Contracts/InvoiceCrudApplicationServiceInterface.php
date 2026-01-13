<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\DTO\InvoiceDTO;

/**
 * Contract for invoice CRUD operations
 */
interface InvoiceCrudApplicationServiceInterface
{
    /**
     * Create new invoice
     */
    public function create(array $createData, UserId $userId): InvoiceDTO;

    /**
     * Update existing invoice
     */
    public function update(array $updateData, InvoiceId $invoiceId, UserId $userId): InvoiceDTO;

    /**
     * Delete invoice
     */
    public function delete(InvoiceId $invoiceId, UserId $userId): bool;

    /**
     * Duplicate existing invoice
     */
    public function duplicate(InvoiceId $invoiceId, UserId $userId): InvoiceDTO;

    /**
     * Get invoice by ID for editing
     */
    public function getForEdit(InvoiceId $invoiceId, UserId $userId): InvoiceDTO;

    /**
     * Soft delete invoice (if applicable)
     */
    public function softDelete(InvoiceId $invoiceId, UserId $userId): bool;

    /**
     * Restore soft-deleted invoice (if applicable)
     */
    public function restore(InvoiceId $invoiceId, UserId $userId): bool;
}
