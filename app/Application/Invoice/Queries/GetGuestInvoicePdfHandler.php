<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\Locale;

final class GetGuestInvoicePdfHandler
{
    public function __construct(
        private readonly TemporaryInvoiceStoreInterface $store,
        private readonly TemporaryInvoicePrintDataFactoryInterface $printData,
        private readonly InvoicePdfRendererInterface $renderer,
        private readonly ClockInterface $clock,
        private readonly Locale $locale,
    ) {}

    public function handle(GetGuestInvoicePdf $query): PdfInvoiceDTO
    {
        $stored = $this->store->get($query->token)
            ?? throw new InvoiceNotFoundException('Guest invoice expired or unknown');
        $printable = $this->printData->fromArray($stored);
        // An unsupported requested language would break the formatters, so it falls back to the current one
        $locale = $this->locale->determineLocale(
            $query->locale,
            $this->locale->getLocale(),
        );
        $pdf = $this->renderer->render(
            $printable,
            $printable->template,
            $locale,
        );
        $now = $this->clock->now();

        return new PdfInvoiceDTO(
            'faktura-' . ($printable->number ?? $now->format('YmdHis')) . '.pdf',
            $pdf->output(),
            $locale,
        );
    }
}
