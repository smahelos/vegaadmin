<?php

namespace App\Application\Invoice\Contracts;

use App\Models\Invoice;

/**
 * Persistence boundary for Invoice aggregate.
 *
 * Wave 2: Initial minimal contract used by InvoiceApplicationService. Will be extended in later waves
 * (creation, querying, filtering) once application layer is refactored.
 */
interface InvoiceWriteRepositoryInterface
{
    public function create(array $data): Invoice;

    /** Update invoice for user (returns updated or throws if not found) */
    public function updateForUser(int $userId, int $invoiceId, array $data): Invoice;

    /** Create an unsaved invoice instance (for form stubs) */
    public function make(array $attributes = []): Invoice;

    /** Mark invoice as paid by assigning paid status id */
    public function markAsPaid(int $id, int $paidStatusId): bool;

    /** Set PDF template for invoice */
    public function setTemplate(int $id, string $template): bool;

    /** Update invoice by ID */
    public function updateById(int $id, array $data): Invoice;

    /** Delete invoice by ID */
    public function deleteById(int $id): bool;
}
