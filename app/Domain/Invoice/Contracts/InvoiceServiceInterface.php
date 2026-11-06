<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\User\ValueObjects\UserId;

interface InvoiceServiceInterface
{
    public function setInvoiceTemplate(InvoiceId $id, string $template): bool;

    /**
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function createInvoice(
        UserId $userId,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO;

    /**
     * Replaces the invoice and its lines.
     *
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function updateInvoice(
        UserId $userId,
        InvoiceId $invoiceId,
        InvoiceWriteData $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO;

    public function generateInvoiceNumber(UserId $userId, int $year): string;
}
