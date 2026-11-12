<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

final readonly class PdfInvoiceDTO
{
    public function __construct(
        public string $filename,
        public string $content,
        // The supported locale the PDF was rendered in, remembered for the viewer
        public string $locale,
    ) {}
}
