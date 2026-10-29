<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Infrastructure\Renderers\Invoice\InvoicePdfRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

trait RendersInvoicePdfHtml
{
    private function renderPdfHtml(
        PrintableInvoiceDTO $invoice,
        string $template = 'default',
        string $locale = 'cs',
    ): string {
        $html = '';
        Pdf::shouldReceive('loadView')->once()->andReturnUsing(
            function (string $view, array $data) use (&$html): PdfDocument {
                $html = view($view, $data)->render();

                return app(PdfDocument::class);
            },
        );
        app(InvoicePdfRenderer::class)->render(
            $invoice,
            $template,
            $locale,
        );

        return $html;
    }
}
