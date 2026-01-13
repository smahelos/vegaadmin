<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Read repository that returns DTOs instead of Eloquent models.
 */
interface ClientDtoReadRepositoryInterface
{
    /** @return array<int,string> */
    public function getClientsForDropdown(int $userId): array;

    public function getDefaultClient(int $userId): ?ClientDTO;
    
    public function findById(PartyId $id): ?ClientDTO;
    
    /** Find by id without scoping to current user (admin/ownership checks handled at higher layer) */
    public function findByIdAny(PartyId $id): ?ClientDTO;

    /** Find by id scoped to a specific user */
    public function findByIdForUser(PartyId $id, UserId $userId): ?ClientDTO;
    
    /** @return array<int, ClientDTO> */
    public function allForAdmin(): array;

    /** @return array<int, ClientDTO> */
    public function allForUser(int $userId): array;

    public function firstForUser(int $userId): ?ClientDTO;
}
