<?php

declare(strict_types=1);

namespace App\Application\Invoice\Contracts;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Models\Invoice;

/**
 * The invoice model the show and edit pages render until step 12 replaces them (docs/TECH_DEBT.md).
 */
interface InvoiceReadRepositoryInterface
{
    /** Null when the invoice does not exist or is outside the scope. */
    public function find(InvoiceId $id, ReadScope $scope): ?Invoice;
}
