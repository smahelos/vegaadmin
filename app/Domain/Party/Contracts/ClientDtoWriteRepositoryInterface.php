<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\ClientWriteData;
use App\Domain\Party\ValueObjects\PartyId;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface ClientDtoWriteRepositoryInterface
{
    public function create(ClientWriteData $data): ClientDTO;

    public function unsetOthersDefault(PartyId $currentId, int $userId): void;

    /** Update client by ID with provided attributes */
    public function updateById(PartyId $id, ClientWriteData $data): bool;

    /** Delete client by ID */
    public function deleteById(PartyId $id): bool;

    /** Check if client has linked invoices */
    public function hasLinkedInvoices(PartyId $id): bool;
}
