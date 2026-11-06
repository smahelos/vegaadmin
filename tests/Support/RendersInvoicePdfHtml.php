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
        $expectation = Pdf::shouldReceive('loadView');
        $expectation->once();
        $expectation->andReturnUsing(
            function (string $view, array $data) use (&$html): PdfDocument {
                $pdfView = view($view, $data);
                $html = $pdfView->render();

                return app(PdfDocument::class);
            },
        );
        $renderer = app(InvoicePdfRenderer::class);
        $renderer->render(
            $invoice,
            $template,
            $locale,
        );

        return $html;
    }
}
