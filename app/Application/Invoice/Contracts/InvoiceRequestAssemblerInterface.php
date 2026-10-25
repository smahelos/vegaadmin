<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceMutationPayload;
use App\Http\Requests\InvoiceRequest;

/**
 * Assembles a normalized InvoiceMutationPayload from an HTTP request.
 */
interface InvoiceRequestAssemblerInterface
{
    public function assemble(InvoiceRequest $request): InvoiceMutationPayload;
}
