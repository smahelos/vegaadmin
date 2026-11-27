<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Application\Invoice\Support\StoredInvoicePdf;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;

final class GetIssuedInvoicePdfHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly StoredInvoicePdf $storedPdf,
    ) {}

    public function handle(GetIssuedInvoicePdf $query, AllOwnersScope $scope): PdfInvoiceDTO
    {
        $invoice = $this->invoices->findById($query->id, $scope);
        // A draft has no stored PDF; the admin downloads only issued documents
        if ($invoice === null || $invoice->issue === null) {
            throw new InvoiceNotFoundException('Issued invoice not found');
        }

        return $this->storedPdf->read($invoice, $invoice->issue);
    }
}
