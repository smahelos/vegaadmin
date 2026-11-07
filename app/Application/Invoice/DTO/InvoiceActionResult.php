<?php

namespace App\Application\Invoice\DTO;

use App\Models\Invoice;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unified result object for invoice actions (create/update, status/template changes, PDF generation).
 * Provides common fields and convenience factories to reduce proliferation of specialized result DTOs.
 */
class InvoiceActionResult
{
    public function __construct(
        public readonly InvoiceActionStatus $status,
        public readonly ?string $message = null,
        /** Mutated / affected invoice when relevant. */
        public readonly ?Invoice $invoice = null,
        /** PDF response when action generates a PDF. */
        public readonly ?Response $response = null,
    ) {}

    /** Success factory */
    public static function success(
        ?string $message = null,
        ?Invoice $invoice = null,
        ?Response $response = null,
    ): self {
        return new self(
            InvoiceActionStatus::SUCCESS,
            $message,
            $invoice,
            $response,
        );
    }

    /** Failure factory */
    public static function failure(?string $message = null): self
    {
        return new self(
            InvoiceActionStatus::FAILURE,
            $message,
        );
    }
}
