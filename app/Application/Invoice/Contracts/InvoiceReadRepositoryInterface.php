<?php

namespace App\Application\Invoice\Contracts;

use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Persistence boundary for Invoice aggregate.
 *
 * Wave 2: Initial minimal contract used by InvoiceApplicationService. Will be extended in later waves
 * (creation, querying, filtering) once application layer is refactored.
 */
interface InvoiceReadRepositoryInterface
{
    /** Find invoice by ID (no ownership scoping here – enforced in application layer). */
    public function findById(int $id): Invoice;

    /** Find invoice for user by ID */
    public function findForUser(int $userId, int $invoiceId): ?Invoice;

    /** List invoices for a user */
    public function listForUser(int $userId): Collection;

    /** List all invoices (admin-only use cases) */
    public function listAll(): Collection;

    /** Get invoices for dropdown/select options for a user. */
    public function getInvoicesForDropdown(int $userId): array;

    /** List all invoices that have non-null invoice_text (used for bulk sync) */
    public function listWithInvoiceText(): Collection;

    /**
     * Get all products for admin
     * @return Collection
     */
    public function allForAdmin(): Collection;

    /**
     * Get all products for specific user
     * @return Collection
     */
    public function allForUser(int $userId): Collection;

    /**
     * Find last invoice for specific year
     */
    public function findLastInvoiceForYear(int $userId, int $year): ?Invoice;
}
