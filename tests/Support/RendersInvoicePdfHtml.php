<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Infrastructure\Renderers\Invoice\InvoicePdfRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Mockery;

trait RendersInvoicePdfHtml
{
    private function renderPdfHtml(
        PrintableInvoiceDTO $invoice,
        string $locale = 'cs',
    ): string {
        $html = '';
        $expectation = Pdf::shouldReceive('loadView');
        $expectation->once();
        $expectation->andReturnUsing(
            function (string $view, array $data) use (&$html): PdfDocument {
                $pdfView = view($view, $data);
                $html = $pdfView->render();
                // The snapshot pins the HTML; dompdf does not run
                $pdf = Mockery::mock(PdfDocument::class);
                $output = $pdf->shouldReceive('output');
                $output->andReturn('');

                return $pdf;
            },
        );
        $renderer = app(InvoicePdfRenderer::class);
        $renderer->render($invoice, $locale);

        return $html;
    }
}
