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

    /** Create an unsaved invoice instance (for form stubs) */
    public function make(array $attributes = []): Invoice;
}
