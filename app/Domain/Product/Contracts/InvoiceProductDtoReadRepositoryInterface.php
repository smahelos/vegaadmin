<?php

declare(strict_types=1);

namespace App\Domain\Product\Contracts;

use App\Domain\Invoice\DTO\InvoiceLineAmountDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;

interface InvoiceProductDtoReadRepositoryInterface
{
    /** @return list<InvoiceLineAmountDTO> */
    public function amountsForInvoice(InvoiceId $invoiceId): array;
}
