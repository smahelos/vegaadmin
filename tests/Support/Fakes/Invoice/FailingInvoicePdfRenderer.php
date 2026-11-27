<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Invoice;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use RuntimeException;

final class FailingInvoicePdfRenderer implements InvoicePdfRendererInterface
{
    public function render(PrintableInvoiceDTO $data, string $locale): string
    {
        throw new RuntimeException('The PDF could not be rendered');
    }
}
