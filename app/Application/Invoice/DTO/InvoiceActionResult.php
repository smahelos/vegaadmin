<?php

namespace App\Application\Invoice\DTO;

use App\Models\Invoice;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unified result object for invoice actions (create/update, status/template changes, limits fetch, PDF generation).
 * Provides common fields and convenience factories to reduce proliferation of specialized result DTOs.
 */
class InvoiceActionResult
{
    public function __construct(
        public readonly InvoiceActionStatus $status,
        public readonly ?string $message = null,
        /** Generic associative payload for arbitrary data (limits, extra context). */
        public readonly ?array $data = null,
        /** Mutated / affected invoice when relevant. */
        public readonly Invoice|null $invoice = null,
        /** PDF response when action generates a PDF. */
        public readonly ?Response $response = null,
        /** Underlying error (not exposed to end-user, for logging/inspection in tests). */
        public readonly ?\Throwable $error = null,
    ) {}

    /** Success factory */
    public static function success(string|null $message = null, array|null $data = null, Invoice|null $invoice = null, Response|null $response = null): self
    {
        return new self(InvoiceActionStatus::SUCCESS, $message, $data, $invoice, $response);
    }

    /** Failure factory */
    public static function failure(?string $message = null, ?\Throwable $error = null, ?array $data = null): self
    {
        return new self(InvoiceActionStatus::FAILURE, $message, $data, null, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->status === InvoiceActionStatus::SUCCESS;
    }
}
