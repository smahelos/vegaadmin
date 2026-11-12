<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetInvoicePartiesHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
    ) {}

    public function handle(GetInvoiceParties $query, ReadScope $scope): DocumentParties
    {
        $invoice = $this->invoices->findById($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');

        return $invoice->parties;
    }
}
