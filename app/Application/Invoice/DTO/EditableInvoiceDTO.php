<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Domain\Invoice\DTO\InvoiceDTO;

/**
 * The invoice edit page: the form and the stored invoice that fills it.
 */
final readonly class EditableInvoiceDTO
{
    public function __construct(
        public InvoiceFormDTO $form,
        public InvoiceDTO $invoice,
    ) {}
}
