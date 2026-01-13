<?php

namespace App\Application\Invoice\Contracts;

interface GuestInvoiceApplicationServiceInterface
{
    /**
     * Store a guest (temporary) invoice and prepare response payload.
     *
     * @param array<string,mixed> $validatedData
     * @param array<int,mixed>|string|null $invoiceProducts
     * @param string|null $requestedLocale
     * @return array<string,mixed>
     */
    public function storeGuestInvoice(array $validatedData, array|string|null $invoiceProducts, ?string $requestedLocale): array;

    /**
     * Prepare data needed to render guest create invoice form.
     * @return array<string,mixed>
     */
    public function prepareGuestCreateData(): array;

    /**
     * Generate (stream or download) invoice PDF by temporary token.
     * @param string|null $token
     * @param string|null $requestedLocale
     * @param bool $preview
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generatePdfByToken(?string $token, ?string $requestedLocale, bool $preview = false): \Symfony\Component\HttpFoundation\Response;

    /**
     * Delete temporary invoice identified by token (if session token matches) and clear session markers.
     */
    public function deleteTemporary(string $token): bool;
}
