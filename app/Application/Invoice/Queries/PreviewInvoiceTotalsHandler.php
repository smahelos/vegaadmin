<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\Services\InvoiceProductOwnership;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class PreviewInvoiceTotalsHandler
{
    public function __construct(
        private readonly InvoiceProductOwnership $ownership,
    ) {}

    public function handle(PreviewInvoiceTotals $query, ReadScope $scope): InvoiceTotals
    {
        $this->ownership->assertOwned($scope, $query->lines);

        return InvoiceTotals::fromProducts($query->currency, $query->lines);
    }
}
