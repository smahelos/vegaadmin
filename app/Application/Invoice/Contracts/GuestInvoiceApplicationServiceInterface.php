<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;

interface GuestInvoiceApplicationServiceInterface
{
    /**
     * Store a guest (temporary) invoice and prepare response payload.
     *
     * @param  array<string,mixed>  $validatedData  validated request input, items under 'invoice-products'
     * @return array<string,mixed>
     */
    public function storeGuestInvoice(
        array $validatedData,
        DocumentPartyDTO $supplier,
        DocumentPartyDTO $customer,
        ?DocumentAccountDTO $account,
        ?string $requestedLocale,
    ): array;

    /**
     * Prepare data needed to render guest create invoice form.
     *
     * @return array<string,mixed>
     */
    public function prepareGuestCreateData(): array;

    /**
     * Delete temporary invoice identified by token (if session token matches) and clear session markers.
     */
    public function deleteTemporary(string $token): bool;
}
