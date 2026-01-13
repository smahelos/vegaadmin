<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceMutationPayload;
use App\Http\Requests\InvoiceRequest;
use App\Models\User;

/**
 * Assembles a normalized InvoiceMutationPayload from an HTTP request.
 */
interface InvoiceRequestAssemblerInterface
{
    public function assemble(?User $user, InvoiceRequest $request, bool $guest = false): InvoiceMutationPayload;
}
