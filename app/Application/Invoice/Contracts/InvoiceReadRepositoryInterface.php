<?php

namespace App\Application\Invoice\Contracts;

use App\Models\Invoice;

/**
 * Persistence boundary for Invoice aggregate.
 *
 * Wave 2: Initial minimal contract used by InvoiceApplicationService. Will be extended in later waves
 * (creation, querying, filtering) once application layer is refactored.
 */
interface InvoiceReadRepositoryInterface
{
    /** Find invoice for user by ID */
    public function findForUser(int $userId, int $invoiceId): ?Invoice;
}
