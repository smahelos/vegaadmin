<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contract for invoice CRUD operations
 */
interface InvoiceCrudApplicationServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        UserId $userId,
        array $data,
        array $products,
    ): InvoiceDTO;

    /**
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        UserId $userId,
        InvoiceId $invoiceId,
        array $data,
        array $products,
    ): InvoiceDTO;

    /**
     * Delete invoice
     */
    public function delete(InvoiceId $invoiceId, UserId $userId): bool;

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
