<?php

namespace App\Application\Invoice\Contracts;

use Symfony\Component\HttpFoundation\Response;

interface GuestInvoiceApplicationServiceInterface
{
    /**
     * Store a guest (temporary) invoice and prepare response payload.
     *
     * @param  array<string,mixed>  $validatedData  validated request input, items under 'invoice-products'
     * @return array<string,mixed>
     */
    public function storeGuestInvoice(array $validatedData, ?string $requestedLocale): array;

    /**
     * Prepare data needed to render guest create invoice form.
     *
     * @return array<string,mixed>
     */
    public function prepareGuestCreateData(): array;

    /**
     * Generate (stream or download) invoice PDF by temporary token.
     */
    public function generatePdfByToken(
        ?string $token,
        ?string $requestedLocale,
        bool $preview = false,
    ): Response;

    /**
     * Delete temporary invoice identified by token (if session token matches) and clear session markers.
     */
    public function deleteTemporary(string $token): bool;
}
